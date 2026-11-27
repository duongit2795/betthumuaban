<?php
defined('APP') or exit;

/** Xử lý update Telegram nhận qua webhook. */
class BotHandler
{
    private array $bot;
    private Telegram $api;
    private int $botTgId;
    private string $botUsername;

    public function __construct(array $bot)
    {
        $this->bot = $bot;
        $this->api = new Telegram($bot['token']);
        $this->botTgId = (int)($bot['tg_bot_id'] ?: explode(':', $bot['token'])[0]);
        $this->botUsername = (string)$bot['username'];
    }

    public function handle(array $u): void
    {
        if (!$this->tenantGate($u)) return;
        if (isset($u['my_chat_member'])) $this->onMyChatMember($u['my_chat_member']);
        elseif (isset($u['chat_member'])) $this->onChatMember($u['chat_member']);
        elseif (isset($u['message'])) $this->onMessage($u['message']);
        elseif (isset($u['edited_message'])) $this->onMessage($u['edited_message'], true);
        elseif (isset($u['message_reaction'])) $this->onReaction($u['message_reaction']);
        elseif (isset($u['chat_join_request'])) {
            $jr = $u['chat_join_request'];
            if ($this->isGroup($jr['chat'])) {
                $g = Repo::upsertGroup($jr['chat'], (int)$this->bot['id']);
                if (Escrow::onJoinRequest($this->api, $g, $jr)) return; // phòng GDTG: chỉ duyệt người mua / người bán
                if (!NameGuard::onRequest($this->api, $g, $jr)) AntiRaid::onRequest($this->api, $g, $jr); // tên chứa từ cấm → từ chối luôn
            }
        }
        elseif (isset($u['callback_query'])) {
            $cbq = $u['callback_query'];
            if (UnbanRequest::onCallback($this->api, $cbq)) return; // admin duyệt / từ chối yêu cầu mở band
            if (Escrow::onCallback($this->api, $cbq)) return;       // nút trong phòng giao dịch trung gian
            if ($cap = Captcha::onCallback($this->api, $cbq)) { $this->afterCaptcha($cap); return; }
            if (!UsernameGuard::onCallback($this->api, $cbq) && !UpdateNotifier::onCallback($this->api, $cbq)) TgLogin::onCallback($this->api, $cbq);
        }
    }

    /**
     * Cho thuê: bỏ qua cập nhật khi
     *  - nhóm đang do bot của chủ khác quản lý (không cho bot khác "cướp" nhóm),
     *  - bot của khách thuê hết hạn gói / bị khoá / nhóm vượt số lượng của gói.
     */
    private function tenantGate(array $u): bool
    {
        $chat = $u['message']['chat'] ?? $u['edited_message']['chat'] ?? $u['chat_member']['chat'] ?? $u['my_chat_member']['chat']
            ?? $u['message_reaction']['chat'] ?? $u['chat_join_request']['chat'] ?? $u['callback_query']['message']['chat'] ?? null;
        $botId = (int)$this->bot['id'];
        $isGroup = $chat && $this->isGroup($chat);
        $g = $isGroup ? DB::get('SELECT id, bot_id, active FROM tg_groups WHERE chat_id = ?', [$chat['id']]) : null;
        if ($g && $g['active'] && $g['bot_id'] && (int)$g['bot_id'] !== $botId && Tenant::botOwner((int)$g['bot_id']) !== Tenant::botOwner($botId)
            && DB::val('SELECT enabled FROM tg_bots WHERE id = ?', [$g['bot_id']])) return false;
        if (!Tenant::botOwner($botId) || isset($u['my_chat_member'])) return true; // bot chủ web; bot được thêm / bị xoá luôn ghi nhận
        $why = Tenant::blockReason($botId, $g ? (int)$g['id'] : null);
        if (!$why) return true;
        // Báo lý do tối đa 1 lần / ngày ở mỗi nhóm, 1 lần / giờ trong chat riêng
        $key = $isGroup ? 'tn_blk_' . $g['id'] : null;
        if ($isGroup && $g && isset($u['message']) && Settings::get($key) !== date('Ymd')) {
            Settings::set($key, date('Ymd'));
            $this->api->trySend($chat['id'], '⏸ Bot tạm ngừng quản lý nhóm này: ' . tg_esc($why) . '.');
        } elseif (!$isGroup && isset($u['message']['chat']['id'])) {
            $cid = $u['message']['chat']['id'];
            $last = (int)DB::val('SELECT last_at FROM tg_rule_hits WHERE rule_id = -7 AND group_id = 0 AND tg_user_id = ?', [$cid]);
            if (now() - $last > 3600) {
                DB::run('DELETE FROM tg_rule_hits WHERE rule_id = -7 AND group_id = 0 AND tg_user_id = ?', [$cid]);
                DB::insert('tg_rule_hits', ['rule_id' => -7, 'group_id' => 0, 'tg_user_id' => $cid, 'last_at' => now()]);
                $this->api->trySend($cid, '⏸ Bot đang tạm ngừng hoạt động: ' . tg_esc($why) . '.');
            }
        }
        return false;
    }

    private function isGroup(array $chat): bool
    {
        return in_array($chat['type'] ?? '', ['group', 'supergroup'], true);
    }

    // ---------- Bot được thêm / bị xoá ----------
    private function onMyChatMember(array $cm): void
    {
        $chat = $cm['chat'];
        if (($chat['type'] ?? '') === 'private') { // người dùng chặn / bỏ chặn bot
            DmBroadcast::privateStatus((int)$this->bot['id'], $chat['id'], (string)($cm['new_chat_member']['status'] ?? ''));
            return;
        }
        if (!$this->isGroup($chat)) return;
        $group = Repo::upsertGroup($chat, (int)$this->bot['id']);
        $st = $cm['new_chat_member']['status'] ?? '';
        $actor = Repo::name(['first_name' => $cm['from']['first_name'] ?? '', 'last_name' => $cm['from']['last_name'] ?? null]);
        if (in_array($st, ['left', 'kicked'], true)) {
            DB::run('UPDATE tg_groups SET active = 0, bot_is_admin = 0 WHERE id = ?', [$group['id']]);
            Repo::log((int)$group['id'], $actor, 'bot_removed', null, 'Bot bị xoá khỏi nhóm');
            return;
        }
        DB::run('UPDATE tg_groups SET active = 1, bot_is_admin = ? WHERE id = ?', [$st === 'administrator' ? 1 : 0, $group['id']]);
        $old = $cm['old_chat_member']['status'] ?? '';
        if (in_array($old, ['left', 'kicked'], true) || $old === '') {
            Repo::log((int)$group['id'], $actor, 'bot_added', null, 'Bot được thêm vào nhóm');
            $count = $this->api->tryCall('getChatMemberCount', ['chat_id' => $chat['id']]);
            if ($count) DB::run('UPDATE tg_groups SET member_count = ? WHERE id = ?', [$count, $group['id']]);
            $why = Tenant::blockReason((int)$this->bot['id'], (int)$group['id']);
            if ($why) $this->api->trySend($chat['id'], '⏸ Bot chưa thể quản lý nhóm này: ' . tg_esc($why) . '. Chủ bot cần nâng cấp / gia hạn gói trên web.');
            else $this->api->trySend($chat['id'], "🤖 Xin chào! Tôi là bot quản lý nhóm.\nHãy cấp quyền <b>Quản trị viên</b> (xoá tin, cấm thành viên) để tôi hoạt động đầy đủ.\nGõ /" . Commands::name('help') . " để xem lệnh.");
        }
        Repo::tgAdmins(Repo::group((int)$group['id']), $this->api, true);
    }

    // ---------- Thay đổi trạng thái thành viên (cần bot là admin) ----------
    private function onChatMember(array $cm): void
    {
        if (!$this->isGroup($cm['chat'])) return;
        $group = Repo::upsertGroup($cm['chat'], (int)$this->bot['id']);
        if (Escrow::isRoom((int)$group['id'])) return; // phòng GDTG: không áp dụng captcha / chống raid / cấm tên…
        $user = $cm['new_chat_member']['user'];
        $st = $cm['new_chat_member']['status'] ?? '';
        $map = ['kicked' => 'banned', 'left' => 'left', 'member' => 'member', 'restricted' => null, 'administrator' => 'member', 'creator' => 'member'];
        $m = Repo::upsertMember((int)$group['id'], $user);
        if ($st === 'restricted') {
            $can = $cm['new_chat_member']['can_send_messages'] ?? true;
            $isMember = $cm['new_chat_member']['is_member'] ?? true;
            $new = !$isMember ? 'left' : (!$can ? 'muted' : 'member');
            $until = (int)($cm['new_chat_member']['until_date'] ?? 0);
            DB::run('UPDATE tg_members SET status = ?, muted_until = ? WHERE id = ?', [$new, $new === 'muted' ? ($until ?: null) : null, $m['id']]);
        } elseif (isset($map[$st])) {
            DB::run('UPDATE tg_members SET status = ? WHERE id = ?', [$map[$st], $m['id']]);
        }
        DB::run('UPDATE tg_members SET is_admin = ? WHERE id = ?', [in_array($st, ['administrator', 'creator'], true) ? 1 : 0, $m['id']]);
        $nm = $cm['new_chat_member'];
        $oldSt = $cm['old_chat_member']['status'] ?? '';
        $wasOut = in_array($oldSt, ['left', 'kicked'], true) || ($oldSt === 'restricted' && empty($cm['old_chat_member']['is_member']));
        $isIn = $st === 'member' || ($st === 'restricted' && !empty($nm['is_member']));
        if ($wasOut && $isIn && empty($user['is_bot'])) {
            UnbanRequest::onJoin($group, $user);
            $by = (int)($cm['from']['id'] ?? 0);
            if (!NameGuard::check($this->api, $group, $user, in_array($st, ['administrator', 'creator'], true) || $this->isAdmin($group, (int)$user['id']), 'join'))
            if (!AntiRaid::onJoin($this->api, $group, $user, in_array($st, ['administrator', 'creator'], true), $by && $by != $user['id'] ? $by : null, fn(int $id) => $this->isAdmin($group, $id)))
            Captcha::start($this->api, (int)$this->bot['id'], $group, $user, $by && $by != $user['id'] ? $by : null, fn(int $id) => $this->isAdmin($group, $id));
        } elseif (in_array($st, ['left', 'kicked'], true)) {
            Captcha::left($group, $user['id'], $this->api);
        }
        if (in_array($st, ['member', 'restricted'], true) && array_key_exists('tag', $nm)) MemberTag::seen((int)$group['id'], $user['id'], $nm['tag']);
        elseif ($st === 'administrator' && array_key_exists('custom_title', $nm)) MemberTag::seen((int)$group['id'], $user['id'], $nm['custom_title']);
        if (in_array($st, ['administrator', 'creator'], true) || in_array($cm['old_chat_member']['status'] ?? '', ['administrator', 'creator'], true)) {
            DB::run('UPDATE tg_groups SET admins_at = 0 WHERE id = ?', [$group['id']]);
        }
    }

    // ---------- Thả cảm xúc (cần bot là quản trị viên) ----------
    private function onReaction(array $mr): void
    {
        if (!$this->isGroup($mr['chat'])) return;
        $group = Repo::upsertGroup($mr['chat'], (int)$this->bot['id']);
        if (!Repo::settings($group)['commands_enabled']) return;
        Rules::onReaction($this->api, $this->botTgId, $group, $mr, fn(int $uid) => $this->isAdmin($group, $uid), (int)$this->bot['id']);
    }

    // ---------- Tin nhắn ----------
    private function onMessage(array $msg, bool $edited = false): void
    {
        $chat = $msg['chat'];
        if (($chat['type'] ?? '') === 'private') {
            if (!$edited) $this->onPrivate($msg);
            return;
        }
        if (!$this->isGroup($chat)) return;

        if (isset($msg['migrate_to_chat_id'])) {
            DB::run('UPDATE tg_groups SET chat_id = ?, type = ? WHERE chat_id = ?', [$msg['migrate_to_chat_id'], 'supergroup', $chat['id']]);
            return;
        }
        if (isset($msg['migrate_from_chat_id'])) return;

        $group = Repo::upsertGroup($chat, (int)$this->bot['id']);
        $gid = (int)$group['id'];
        $s = Repo::settings($group);

        // Lưu tin nhắn cho tab Chat trên web
        if ($edited) ChatLog::edited($msg);
        elseif (!empty($msg['new_chat_members'])) {
            $names = implode(', ', array_map(fn($u) => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')), $msg['new_chat_members']));
            $by = $msg['from'] ?? null;
            $self = $by && count($msg['new_chat_members']) === 1 && $msg['new_chat_members'][0]['id'] == $by['id'];
            ChatLog::store($msg, $gid, 'service', '➕ ' . $names . ($self || !$by ? ' đã tham gia nhóm' : ' được ' . trim($by['first_name'] ?? '') . ' thêm vào nhóm'));
        } elseif (!empty($msg['left_chat_member'])) {
            ChatLog::store($msg, $gid, 'service', '➖ ' . trim(($msg['left_chat_member']['first_name'] ?? '') . ' ' . ($msg['left_chat_member']['last_name'] ?? '')) . ' đã rời nhóm');
        } elseif (isset($msg['new_chat_title'])) {
            ChatLog::store($msg, $gid, 'service', '✏️ Đổi tên nhóm thành "' . $msg['new_chat_title'] . '"');
        } elseif (isset($msg['pinned_message'])) {
            ChatLog::markPinned($chat['id'], $msg['pinned_message']['message_id']);
        } else {
            ChatLog::store($msg, $gid);
        }

        // Phòng giao dịch trung gian: chỉ xử lý việc giao dịch (tin nhắn đã lưu làm bằng chứng ở trên)
        if (Escrow::isRoom($gid)) {
            if (!$edited) Escrow::onRoomMessage($this->api, $group, $msg);
            return;
        }
        if (!$edited && preg_match('~^/gdtg(?:@\w+)?(?:\s|$)~i', (string)($msg['text'] ?? ''))) { $this->escrowCmd($msg); return; }

        if (!empty($msg['new_chat_members'])) {
            $this->onJoin($group, $s, $msg);
            return;
        }
        if (!empty($msg['left_chat_member'])) {
            $this->onLeave($group, $s, $msg);
            return;
        }
        if (isset($msg['new_chat_title']) || isset($msg['pinned_message']) || isset($msg['new_chat_photo'])) {
            if ($s['delete_service_messages'] && isset($msg['new_chat_photo'])) $this->del($chat['id'], $msg['message_id']);
            return;
        }
        if (empty($msg['from'])) return;

        $from = $msg['from'];
        // Admin ẩn danh hoặc kênh liên kết
        $anonAdmin = isset($msg['sender_chat']) && $msg['sender_chat']['id'] == $chat['id'];
        $isLinkedChannel = !empty($msg['is_automatic_forward']);
        if ($isLinkedChannel) return;

        $member = Repo::upsertMember($gid, $from);
        if (empty($msg['sender_chat']) && !$edited) MemberTag::seen($gid, $from['id'], $msg['sender_tag'] ?? null, true); // tag hiện tại trên Telegram
        $isAdmin = $anonAdmin || $this->isAdmin($group, (int)$from['id']);
        $text = (string)($msg['text'] ?? $msg['caption'] ?? '');

        // Bộ lọc vi phạm
        if (!($isAdmin && $s['admins_exempt']) && empty($from['is_bot'])) {
            $vs = $s;
            if ($s['anti_link'] && Vip::perk('perk_links', $from['id'], $gid)) $vs['anti_link'] = false; // VIP được gửi link
            $violation = $this->checkViolation($msg, $vs, $text);
            if ($violation) {
                $this->del($chat['id'], $msg['message_id']);
                ChatLog::markDeleted($chat['id'], $msg['message_id'], $violation);
                $ev = Moderation::evidence($msg);
                Repo::log($gid, 'Bot', 'delete', $member, $violation, ['source' => 'bot', 'reason' => $violation, 'link' => Moderation::link($group, $msg['message_id'])], $ev);
                if ($s['violation_warn']) {
                    $this->safe(fn() => Moderation::perform('warn', $group, ['tgUserId' => $from['id'], 'actor' => 'Bot', 'source' => 'bot', 'reason' => $violation, 'evidence' => $ev]));
                } else {
                    $this->api->trySend($chat['id'], '🚫 ' . Repo::mention($member) . ' — tin nhắn bị xoá: ' . tg_esc($violation));
                }
                return;
            }
        }

        if ($edited) return;

        // Tên / @username chứa từ cấm (người đổi tên sau khi vào, thành viên cũ)
        if (empty($msg['sender_chat']) && NameGuard::check($this->api, $group, $from, $isAdmin, 'message', $msg)) return;

        // Yêu cầu đặt Username Telegram (Lệnh mặc định → Tính năng mặc định)
        if (empty($msg['sender_chat']) && UsernameGuard::check($this->api, (int)$this->bot['id'], $group, $from, $msg, $isAdmin)) return;

        // Học tập bắt buộc: chưa hoàn thành bài học → xoá tin + nhắc vào bot học
        if (empty($msg['sender_chat']) && Learn::check($this->api, $this->bot, $group, $from, $msg, $isAdmin)) return;

        // Điều kiện nhắn tin (VD cần ≥ 50 điểm ở nhóm A mới chat được ở nhóm B)
        if (empty($msg['sender_chat']) && ChatGate::check($this->api, (int)$this->bot['id'], $group, $from, $msg, $isAdmin)) return;

        // Chống flood
        $t = now();
        if ($s['anti_flood'] && !$isAdmin && empty($from['is_bot']) && $s['flood_limit'] > 0) {
            if ($t - (int)$member['flood_start'] > max(1, $s['flood_seconds'])) {
                DB::run('UPDATE tg_members SET flood_start = ?, flood_count = 1 WHERE id = ?', [$t, $member['id']]);
            } else {
                $cnt = (int)$member['flood_count'] + 1;
                DB::run('UPDATE tg_members SET flood_count = ? WHERE id = ?', [$cnt, $member['id']]);
                if ($cnt > $s['flood_limit']) {
                    $this->del($chat['id'], $msg['message_id']);
                    if ($member['status'] !== 'muted') {
                        DB::run('UPDATE tg_members SET flood_count = 0 WHERE id = ?', [$member['id']]);
                        $this->safe(fn() => Moderation::perform('mute', $group, [
                            'tgUserId' => $from['id'], 'actor' => 'Bot', 'source' => 'bot', 'minutes' => $s['mute_minutes'],
                            'reason' => "Spam: gửi hơn {$s['flood_limit']} tin trong {$s['flood_seconds']} giây", 'evidence' => Moderation::evidence($msg),
                        ]));
                    }
                    return;
                }
            }
        }

        // Gọi admin: gõ @admin / @admins trong tin nhắn
        if (!str_starts_with($text, '/') && !$isAdmin && Commands::names('admins') && preg_match('/(^|[^\w@])@admins?(?![\w])/iu', $text)) {
            $this->callAdmins($group, $msg, $member, trim(preg_replace('/(^|\s)@admins?(?![\w])/iu', ' ', $text)));
        }

        // Lệnh tự tạo chạy trước (ưu tiên hơn lệnh có sẵn trùng tên)
        $isCommand = str_starts_with($text, '/');
        $flags = Rules::run($this->api, $this->botUsername, $this->botTgId, $group, $msg, $member, $isAdmin, $text,
            fn(int $uid) => $this->isAdmin($group, $uid), (int)$this->bot['id']);

        // Đóng dấu ảnh chống copy: ảnh thành viên → gửi lại bản có chữ mờ, xoá ảnh gốc
        if (!($flags & Rules::DELETED) && (!empty($msg['photo']) || !empty($msg['document']))) {
            $this->safe(fn() => Watermark::handle($this->api, $group, $msg, $member, $isAdmin));
        }

        // Đếm tin nhắn & điểm tương tác (tin bị lệnh tự tạo xoá thì không tính)
        $member = DB::get('SELECT * FROM tg_members WHERE id = ?', [$member['id']]);
        if (!($flags & Rules::DELETED)) {
            $hasMedia = (bool)array_intersect(array_keys($msg), ['photo', 'video', 'voice', 'audio', 'document', 'sticker', 'animation', 'video_note']);
            $upd = ['messages' => (int)$member['messages'] + 1, 'last_active' => $t];
            if ($s['points_enabled'] && !$isCommand && empty($from['is_bot'])
                && (mb_strlen(trim($text)) >= $s['points_min_length'] || $hasMedia)
                && $t - (int)$member['last_point_at'] >= $s['points_cooldown']) {
                $upd['points'] = (int)$member['points'] + (int)$s['points_per_message'] * Vip::multiplier($from['id'], $gid);
                $upd['last_point_at'] = $t;
            }
            if ($member['status'] === 'left') $upd['status'] = 'member';
            DB::update('tg_members', $upd, 'id = ?', [$member['id']]);
            Repo::stat($gid, 'messages');
            if (isset($upd['points'])) MemberTag::auto($group, $from['id']); // lên mốc điểm → đổi tag
        }

        if ($isCommand && $s['commands_enabled'] && !($flags & Rules::HANDLED)) {
            $member = DB::get('SELECT * FROM tg_members WHERE id = ?', [$member['id']]);
            $this->onCommand($group, $s, $msg, $member, $isAdmin, $text);
        }
    }

    private function onJoin(array $group, array $s, array $msg): void
    {
        $gid = (int)$group['id'];
        $names = [];
        $captcha = []; // người phải xác minh: chào sau khi qua captcha
        $nameHit = []; // bị xử lý vì tên chứa từ cấm
        foreach ($msg['new_chat_members'] as $u) {
            if ($u['id'] == $this->botTgId) continue;
            if (NameGuard::check($this->api, $group, $u, $this->isAdmin($group, (int)$u['id']), 'join')) { $nameHit[$u['id']] = true; continue; } // tên chứa từ cấm
            $addedBy = !empty($msg['from']) && $msg['from']['id'] != $u['id'] ? (int)$msg['from']['id'] : null;
            if (AntiRaid::onJoin($this->api, $group, $u, $this->isAdmin($group, (int)$u['id']), $addedBy, fn(int $id) => $this->isAdmin($group, $id))) { $captcha[$u['id']] = true; continue; } // chống raid đã xử lý
            if (Captcha::start($this->api, (int)$this->bot['id'], $group, $u, $addedBy, fn(int $id) => $this->isAdmin($group, $id))) $captcha[$u['id']] = true;
            $m = Repo::upsertMember($gid, $u, ['status' => 'member', 'joined' => true]);
            if ($m['status'] === 'left') DB::run("UPDATE tg_members SET status = 'member' WHERE id = ?", [$m['id']]);
            if ($s['points_per_join'] > 0 && !empty($msg['from']) && $msg['from']['id'] != $u['id']) {
                // Thưởng điểm cho người mời
                $inv = Repo::upsertMember($gid, $msg['from']);
                DB::run('UPDATE tg_members SET points = points + ? WHERE id = ?', [$s['points_per_join'], $inv['id']]);
            }
            Repo::stat($gid, 'joins');
            Repo::log($gid, 'Telegram', 'join', $m);
            if (empty($u['is_bot'])) $names[] = $m;
        }
        DB::run('UPDATE tg_groups SET member_count = member_count + ? WHERE id = ?', [count($names), $gid]);
        if ($s['delete_service_messages'] || ($captcha && Captcha::config($group)['delete_join']) || ($nameHit && NameGuard::config($group)['delete_msg'])) $this->del($msg['chat']['id'], $msg['message_id']);
        if ($s['welcome_enabled'] && $names) {
            foreach ($names as $m) {
                if (isset($captcha[$m['tg_user_id']])) continue;
                $this->api->trySend($msg['chat']['id'], $this->tpl($s['welcome_text'], $m, $group));
            }
        }
        // Thành viên mới chưa có username → xử lý theo cài đặt "Yêu cầu đặt Username"
        foreach ($msg['new_chat_members'] as $u) {
            if (isset($nameHit[$u['id']])) continue;
            if ($u['id'] != $this->botTgId) UnbanRequest::onJoin($group, $u); // vào lại sau khi được mở band → tag "Từng Bị Band"
            if ($u['id'] != $this->botTgId && !isset($captcha[$u['id']])) UsernameGuard::check($this->api, (int)$this->bot['id'], $group, $u, null, false, true);
            if ($u['id'] != $this->botTgId) Learn::onJoin($group, $u);
        }
    }

    /** Sau khi bấm nút captcha: qua thì gửi lời chào của nhóm */
    private function afterCaptcha(array $cap): void
    {
        [$res, $group, $m] = $cap;
        if ($res !== 'pass' || !$group || !$m) return;
        $s = Repo::settings($group);
        if ($s['welcome_enabled']) $this->api->trySend($group['chat_id'], $this->tpl($s['welcome_text'], $m, $group));
        UsernameGuard::check($this->api, (int)$this->bot['id'], $group, ['id' => (int)$m['tg_user_id'], 'username' => $m['username'], 'first_name' => $m['first_name']], null, false, true);
    }

    private function onLeave(array $group, array $s, array $msg): void
    {
        $u = $msg['left_chat_member'];
        $gid = (int)$group['id'];
        Captcha::left($group, $u['id'], $this->api);
        if ($s['delete_service_messages']) $this->del($msg['chat']['id'], $msg['message_id']);
        if ($u['id'] == $this->botTgId) return;
        $m = Repo::upsertMember($gid, $u);
        if ($m['status'] !== 'banned') DB::run("UPDATE tg_members SET status = 'left' WHERE id = ?", [$m['id']]);
        DB::run('UPDATE tg_groups SET member_count = CASE WHEN member_count > 0 THEN member_count - 1 ELSE 0 END WHERE id = ?', [$gid]);
        Repo::stat($gid, 'leaves');
        Repo::log($gid, 'Telegram', 'leave', $m);
        if ($s['goodbye_enabled'] && empty($u['is_bot'])) {
            $this->api->trySend($msg['chat']['id'], $this->tpl($s['goodbye_text'], $m, $group));
        }
    }

    private function tpl(string $text, array $m, array $group): string
    {
        // Văn bản do admin nhập được phép dùng HTML của Telegram; biến được escape
        return strtr($text, [
            '{mention}' => Repo::mention($m),
            '{name}' => tg_esc(Repo::name($m)),
            '{username}' => $m['username'] ? '@' . tg_esc($m['username']) : tg_esc(Repo::name($m)),
            '{id}' => (string)$m['tg_user_id'],
            '{group}' => tg_esc($group['title']),
            '{count}' => (string)$group['member_count'],
        ]);
    }

    private function isAdmin(array $group, int $tgUserId): bool
    {
        if (in_array($tgUserId, Repo::tgAdmins($group, $this->api), false)) return true;
        return (bool)DB::get("SELECT id FROM app_users WHERE tg_user_id = ? AND role = 'admin' AND disabled = 0", [$tgUserId]);
    }

    private function checkViolation(array $msg, array $s, string $text): ?string
    {
        if ($s['banned_words'] && $text !== '') {
            foreach ($s['banned_words'] as $w) {
                if ($w !== '' && mb_stripos($text, $w) !== false) return 'Chứa từ cấm';
            }
        }
        if ($s['anti_forward'] && (isset($msg['forward_origin']) || isset($msg['forward_from']) || isset($msg['forward_from_chat']))) {
            return 'Không được chuyển tiếp tin nhắn';
        }
        if ($s['anti_link']) {
            $links = [];
            foreach (array_merge($msg['entities'] ?? [], $msg['caption_entities'] ?? []) as $en) {
                if ($en['type'] === 'text_link') $links[] = $en['url'];
                if ($en['type'] === 'url') $links[] = mb_substr($text, $en['offset'], $en['length']);
            }
            if (preg_match_all('~(https?://\S+|www\.\S+|t\.me/\S+|telegram\.me/\S+)~iu', $text, $mm)) $links = array_merge($links, $mm[0]);
            foreach ($links as $l) {
                $ok = false;
                foreach ($s['link_whitelist'] as $wl) {
                    if ($wl !== '' && stripos($l, $wl) !== false) { $ok = true; break; }
                }
                if (!$ok) return 'Không được gửi link';
            }
        }
        $locks = [
            'lock_photo' => [['photo'], 'ảnh'],
            'lock_video' => [['video', 'video_note'], 'video'],
            'lock_sticker' => [['sticker'], 'sticker'],
            'lock_gif' => [['animation'], 'GIF'],
            'lock_voice' => [['voice', 'audio'], 'âm thanh'],
            'lock_poll' => [['poll'], 'bình chọn'],
        ];
        foreach ($locks as $k => [$keys, $label]) {
            if ($s[$k] && array_intersect($keys, array_keys($msg))) return "Nhóm đang khoá $label";
        }
        if ($s['lock_document'] && isset($msg['document']) && !isset($msg['animation'])) return 'Nhóm đang khoá tệp tin';
        return null;
    }

    // ---------- Lệnh ----------
    private function onCommand(array $group, array $s, array $msg, array $member, bool $isAdmin, string $text): void
    {
        if (!preg_match('~^/([a-zA-Z0-9_]+)(?:@([a-zA-Z0-9_]+))?(?:\s+(.*))?$~s', $text, $mm)) return;
        $typed = strtolower($mm[1]);
        if (!empty($mm[2]) && strcasecmp($mm[2], $this->botUsername) !== 0) return;
        $args = trim($mm[3] ?? '');
        $chatId = $msg['chat']['id'];
        $gid = (int)$group['id'];

        // Đổi tên gõ vào (tên gốc hoặc tên thay thế đặt trên web) thành mã lệnh; lệnh tắt → bỏ qua
        $cmd = $typed === 'start' ? (Commands::names('help') ? 'help' : null) : Commands::resolve($typed, 'group');
        if ($cmd === null) return;
        $scope = Commands::CATALOG[$cmd][0];
        $c = fn(string $k) => '/' . Commands::name($k);

        if ($scope === 'member') {
            if (!$s['member_commands'] && !$isAdmin) return;
            switch ($cmd) {
                case 'help':
                    $line = fn(string $k) => Commands::names($k) ? $c($k) . ' – ' . tg_esc(Commands::config()[$k]['description']) : null;
                    $t = "📖 <b>Lệnh thành viên</b>\n" . implode("\n", array_filter(array_map($line, ['rules', 'me', 'top', 'checkin', 'admins', 'info', 'amnesty', 'id'])));
                    $custom = Rules::helpLines($gid, $isAdmin);
                    if ($custom) $t .= "\n\n✨ <b>Lệnh riêng của nhóm</b>\n" . implode("\n", $custom);
                    if ($isAdmin) {
                        $adm = array_filter(array_map($line, ['warn', 'unwarn', 'resetwarn', 'mute', 'unmute', 'kick', 'ban', 'unban', 'addpoints', 'tag', 'del', 'pin', 'unpin', 'setrules', 'reload']));
                        if ($adm) $t .= "\n\n🛡 <b>Lệnh quản trị</b> (reply tin nhắn hoặc @username/ID)\n" . implode("\n", $adm);
                    }
                    $this->reply($msg, $t);
                    return;
                case 'rules':
                    $this->reply($msg, "📜 <b>Nội quy " . tg_esc($group['title']) . "</b>\n\n" . $s['rules_text']);
                    return;
                case 'id':
                    $t = "🆔 Nhóm: <code>{$chatId}</code>\nBạn: <code>{$member['tg_user_id']}</code>";
                    if (isset($msg['reply_to_message']['from'])) $t .= "\nNgười được reply: <code>{$msg['reply_to_message']['from']['id']}</code>";
                    $this->reply($msg, $t);
                    return;
                case 'me':
                case 'info':
                    $target = $member;
                    if ($cmd === 'info') {
                        [$tm] = $this->resolveTarget($group, $msg, $args);
                        if ($tm) $target = $tm;
                    }
                    $this->reply($msg, $this->profileText($group, $s, $target));
                    return;
                case 'amnesty':
                    $this->reply($msg, $this->amnestyText($group, $msg), $this->webButton('🕊 Mở trang Ân xá', '/amnesty'));
                    return;
                case 'checkin':
                    $this->checkinCmd($group, $msg, $member);
                    return;
                case 'admins':
                    $this->callAdmins($group, $msg, $member, $args);
                    return;
                case 'top':
                    $rows = DB::all("SELECT * FROM tg_members WHERE group_id = ? AND is_bot = 0 AND status <> 'banned' AND points > 0 ORDER BY points DESC, messages DESC LIMIT 10", [$gid]);
                    if (!$rows) { $this->reply($msg, 'Chưa có dữ liệu xếp hạng.'); return; }
                    $medals = ['🥇', '🥈', '🥉'];
                    $lines = [];
                    foreach ($rows as $i => $r) $lines[] = ($medals[$i] ?? ($i + 1) . '.') . ' ' . tg_esc(Repo::name($r)) . " — <b>{$r['points']}</b> điểm";
                    $this->reply($msg, "🏆 <b>Bảng xếp hạng tương tác</b>\n\n" . implode("\n", $lines));
                    return;
            }
        }

        if ($scope !== 'admin') return;
        if (!$isAdmin) {
            $this->reply($msg, '⛔ Bạn không có quyền dùng lệnh này.');
            return;
        }
        $actor = Repo::name($member) . ' (Telegram)';

        switch ($cmd) {
            case 'reload':
                Repo::tgAdmins($group, $this->api, true);
                $this->reply($msg, '✅ Đã làm mới danh sách quản trị viên.');
                return;
            case 'del':
                if (isset($msg['reply_to_message'])) $this->del($chatId, $msg['reply_to_message']['message_id']);
                $this->del($chatId, $msg['message_id']);
                return;
            case 'pin':
            case 'unpin':
                if (!isset($msg['reply_to_message'])) { $this->reply($msg, 'Hãy reply tin nhắn cần ' . ($cmd === 'pin' ? 'ghim' : 'bỏ ghim') . '.'); return; }
                $r = $this->safe(fn() => $this->api->call($cmd === 'pin' ? 'pinChatMessage' : 'unpinChatMessage', ['chat_id' => $chatId, 'message_id' => $msg['reply_to_message']['message_id']]), $msg);
                if ($r !== null) Repo::log($gid, $actor, $cmd, null);
                return;
            case 'setrules':
                $rules = $args !== '' ? $args : ($msg['reply_to_message']['text'] ?? '');
                if ($rules === '') { $this->reply($msg, 'Cú pháp: ' . $c('setrules') . ' nội dung (hoặc reply tin nhắn chứa nội quy)'); return; }
                Repo::saveSettings($group, ['rules_text' => tg_esc($rules)]);
                Repo::log($gid, $actor, 'settings', null, 'Cập nhật nội quy');
                $this->reply($msg, '✅ Đã cập nhật nội quy.');
                return;
        }

        [$target, $rest] = $this->resolveTarget($group, $msg, $args);
        if (!$target) {
            $this->reply($msg, $cmd === 'tag' ? 'Cú pháp: reply tin của thành viên rồi gõ ' . $c('tag') . ' Tên tag (để trống = xoá tag), hoặc ' . $c('tag') . ' @username Tên tag' : 'Hãy reply tin nhắn của thành viên hoặc nhập @username / ID.');
            return;
        }
        if ($target['tg_user_id'] == $this->botTgId) return;
        if ($cmd === 'tag') {
            try {
                $t = MemberTag::set($group, $target['tg_user_id'], $rest, $actor, 'manual', false, 'telegram');
                $this->reply($msg, $t['tag'] ? '🏷 Đã gắn tag <b>' . tg_esc($t['tag']) . '</b> cho ' . Repo::mention($t) : '🏷 Đã xoá tag của ' . Repo::mention($t));
            } catch (HttpError $e) {
                $this->reply($msg, '❌ ' . tg_esc($e->getMessage()));
            }
            return;
        }
        if (in_array($cmd, ['ban', 'kick', 'mute', 'warn'], true) && $this->isAdmin($group, (int)$target['tg_user_id'])) {
            $this->reply($msg, '⛔ Không thể xử lý quản trị viên.');
            return;
        }
        // Bằng chứng: tin nhắn được reply (nếu lệnh dùng bằng cách reply)
        $evMsg = isset($msg['reply_to_message']['from']) && $msg['reply_to_message']['from']['id'] == $target['tg_user_id'] ? $msg['reply_to_message'] : null;
        $o = ['tgUserId' => $target['tg_user_id'], 'actor' => $actor, 'actorTgId' => $member['tg_user_id'], 'source' => 'telegram',
            'reason' => $rest, 'evidence' => Moderation::evidence($evMsg)];
        if ($cmd === 'mute') {
            $parts = preg_split('/\s+/', $rest, 2);
            $mins = Moderation::parseMinutes($parts[0] ?? '');
            if ($mins !== null) { $o['minutes'] = $mins; $o['reason'] = $parts[1] ?? ''; }
        }
        if ($cmd === 'addpoints') {
            $parts = preg_split('/\s+/', $rest, 2);
            if (!is_numeric($parts[0] ?? '')) { $this->reply($msg, 'Cú pháp: ' . $c('addpoints') . ' 10 [lý do] (số âm để trừ)'); return; }
            $o['amount'] = (int)$parts[0];
            $o['reason'] = $parts[1] ?? '';
            $cmd = 'points';
        }
        $this->safe(fn() => Moderation::perform($cmd, $group, $o), $msg);
    }

    /** @return array{0:?array,1:string} [member, phần còn lại của args] */
    private function resolveTarget(array $group, array $msg, string $args): array
    {
        $gid = (int)$group['id'];
        $r = $msg['reply_to_message'] ?? null;
        // Bỏ qua reply vào tin tạo topic (forum) và tin của chính bot
        if ($r && isset($r['from']) && empty($r['forum_topic_created']) && $r['from']['id'] != $this->botTgId) {
            return [Repo::upsertMember($gid, $r['from']), $args];
        }
        foreach ($msg['entities'] ?? [] as $en) {
            if ($en['type'] === 'text_mention' && isset($en['user'])) {
                $m = Repo::upsertMember($gid, $en['user']);
                $name = mb_substr($msg['text'], $en['offset'], $en['length']);
                return [$m, trim(str_replace($name, '', $args))];
            }
        }
        if ($args === '') return [null, ''];
        $parts = preg_split('/\s+/', $args, 2);
        $first = $parts[0];
        $rest = $parts[1] ?? '';
        if (preg_match('/^-?\d{4,}$/', $first)) {
            $m = Repo::member($gid, $first) ?: Repo::upsertMember($gid, ['id' => (int)$first, 'first_name' => $first]);
            return [$m, $rest];
        }
        if (str_starts_with($first, '@')) {
            $m = DB::get('SELECT * FROM tg_members WHERE group_id = ? AND LOWER(username) = ?', [$gid, strtolower(substr($first, 1))]);
            if ($m) return [$m, $rest];
        }
        return [null, $args];
    }

    private function profileText(array $group, array $s, array $m): string
    {
        $rank = 1 + (int)DB::val('SELECT COUNT(*) FROM tg_members WHERE group_id = ? AND is_bot = 0 AND points > ?', [$group['id'], $m['points']]);
        $status = ['member' => 'Thành viên', 'muted' => 'Đang bị cấm chat', 'banned' => 'Bị cấm', 'left' => 'Đã rời nhóm'][$m['status']] ?? $m['status'];
        if ($m['is_admin']) $status = 'Quản trị viên';
        return "👤 <b>" . tg_esc(Repo::name($m)) . "</b>\n"
            . ($m['username'] ? "🔗 @" . tg_esc($m['username']) . "\n" : '')
            . "🆔 <code>{$m['tg_user_id']}</code>\n"
            . "🏷 $status\n"
            . "⭐ Điểm: <b>{$m['points']}</b> (hạng #$rank)\n"
            . "💬 Tin nhắn: {$m['messages']}\n"
            . "⚠️ Cảnh cáo: {$m['warns']}/{$s['warn_limit']}\n"
            . ($m['joined_at'] ? '📅 Tham gia: ' . date('d/m/Y', (int)$m['joined_at']) : '');
    }

    // ---------- Chat riêng ----------
    private function onPrivate(array $msg): void
    {
        $text = trim((string)($msg['text'] ?? ''));
        $from = $msg['from'];
        DmBroadcast::seen((int)$this->bot['id'], $from['id']); // đã nhắn riêng cho bot → nhận được tin nhắn riêng hàng loạt
        if (preg_match('~^/gdtg(?:@\w+)?(?:\s|$)~i', $text)) { $this->escrowCmd($msg); return; }
        if (preg_match('~^/start(?:@\w+)?\s+dm$~i', $text)) {
            $this->reply($msg, "✅ Đã bật <b>nhận thông báo riêng</b> từ nhóm qua bot.\nBạn có thể tắt bất cứ lúc nào bằng cách chặn bot.");
            return;
        }

        // Lệnh gõ vào (tên gốc hoặc tên đặt trên web) → mã lệnh
        $key = null;
        $arg = '';
        $payload = null;
        if (preg_match('~^/([a-zA-Z0-9_]+)(?:@\w+)?(?:\s+(.*))?$~s', $text, $pm)) {
            $typed = strtolower($pm[1]);
            $arg = trim($pm[2] ?? '');
            if ($typed === 'start') { $key = 'start'; $payload = $arg; }
            else $key = Commands::resolve($typed, 'private');
        }
        $c = fn(string $k) => '/' . Commands::name($k);

        // Nút trên bàn phím bot (trang Bàn phím bot): trả lời / mở link, hoặc chuyển sang chức năng có sẵn
        if ($key === null && ($kbBtn = BotKeyboard::match($text))) {
            if (BotKeyboard::respond($this->api, $msg['chat']['id'], $kbBtn, $from)) return;
            $key = ['learn' => 'learn', 'balance' => 'balance', 'points' => 'pme', 'start' => 'start'][$kbBtn['action']] ?? null;
        }

        // Đăng nhập web: /start login_ABC123 (deep link), lệnh đăng nhập + mã, hoặc chỉ gửi mã
        $loginCode = null;
        if ($payload !== null && preg_match('~^login_([A-Za-z0-9]{6})$~', $payload, $lm)) $loginCode = $lm[1];
        elseif ($key === 'login' && preg_match('~^[A-Za-z0-9]{6}$~', $arg)) $loginCode = $arg;
        elseif ($key === null && preg_match('~^[A-Za-z0-9]{6}$~', $text) && Commands::names('login')) $loginCode = $text;
        if ($loginCode !== null && TgLogin::onCode($this->api, $msg, $loginCode)) return;

        // Captcha xác minh qua bot: /start cap_ID
        if ($payload !== null && str_starts_with($payload, 'cap_') && Captcha::privateStart($this->api, $msg, $payload)) return;

        // Học tập: nút "Học tập", /start learn, lệnh học tập, hoặc câu trả lời khi đang học
        if (Learn::onPrivate($this->api, $msg, $key, $payload)) return;
        if ($key === 'login') {
            $this->reply($msg, '🔐 Để đăng nhập web: mở trang web → <b>Đăng nhập bằng Telegram</b> → gửi mã 6 ký tự cho tôi, ví dụ <code>' . $c('login') . ' K7QX4P</code>');
            return;
        }

        // Liên kết tài khoản web
        $code = null;
        if ($payload !== null && preg_match('~^link_([A-Za-z0-9]+)$~', $payload, $mm)) $code = $mm[1];
        elseif ($key === 'link' && $arg !== '') $code = preg_split('/\s+/', $arg)[0];
        elseif ($key === 'link') { $this->reply($msg, 'Cú pháp: ' . $c('link') . ' MÃ (tạo mã ở trang Tài khoản trên web)'); return; }

        if ($code !== null) {
            $u = DB::get('SELECT * FROM app_users WHERE link_code = ?', [strtoupper($code)]);
            if (!$u) { $this->reply($msg, '❌ Mã liên kết không hợp lệ hoặc đã hết hạn. Hãy tạo mã mới trên web.'); return; }
            DB::run('UPDATE app_users SET tg_user_id = NULL WHERE tg_user_id = ? AND id <> ?', [$from['id'], $u['id']]);
            DB::run('UPDATE app_users SET tg_user_id = ?, link_code = NULL WHERE id = ?', [$from['id'], $u['id']]);
            $this->reply($msg, '✅ Đã liên kết Telegram với tài khoản web <b>' . tg_esc($u['username']) . '</b>. Bây giờ bạn có thể xem điểm của mình trên web.');
            return;
        }
        if ($key === 'giftcode') {
            if ($arg === '') { $this->reply($msg, '🎁 Cú pháp: ' . $c('giftcode') . ' MÃ  (VD ' . $c('giftcode') . ' TET2027)'); return; }
            $u = DB::get('SELECT * FROM app_users WHERE tg_user_id = ? AND disabled = 0', [$from['id']]);
            $type = DB::val('SELECT type FROM gift_codes WHERE code = ?', [strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $arg))]);
            if (!$u && $type === 'money') $u = TgLogin::createUser((int)$from['id'], trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')), (string)($from['username'] ?? ''));
            try {
                $r = GiftCode::redeem($u, $from['id'], $arg);
                $sfx = $r['space'] ? '/' . $r['space'] : '';
                $this->reply($msg, "🎁 <b>Nhận quà thành công!</b>\nMã <code>{$r['code']}</code>: " . tg_esc($r['text']), $this->webButton('💳 Mở web', $r['type'] === 'money' ? '/wallet' . $sfx : ($r['type'] === 'vip' ? '/vip' . $sfx : '/')));
            } catch (HttpError $e) {
                $this->reply($msg, '❌ ' . tg_esc($e->getMessage()));
            }
            return;
        }
        if ($key === 'balance') {
            $u = DB::get('SELECT * FROM app_users WHERE tg_user_id = ? AND disabled = 0', [$from['id']]);
            if (!$u) { $this->reply($msg, "💳 Bạn chưa có tài khoản web liên kết với Telegram.\nĐăng nhập web bằng Telegram (" . $c('login') . " MÃ) để có ví và nạp tiền."); return; }
            $sp = $this->space();
            $wc = Wallet::config($sp);
            if ($sp && !$wc['enabled']) { $this->reply($msg, '💳 Nhóm này chưa bật ví.'); return; }
            $t = "💳 <b>Ví của bạn</b>" . ($sp ? ' · ' . tg_esc(Space::title($sp)) : '') . "\n💰 Số dư: <b>" . Wallet::money(Wallet::balance((int)$u['id'], $sp)) . '</b>';
            if ($wc['enabled'] && $wc['account_no']) {
                $t .= "\n\n🏦 <b>Nạp tiền</b> (" . tg_esc(Wallet::BANKS[$wc['bank']] ?? $wc['bank']) . ")\nSố TK: <code>{$wc['account_no']}</code>" . ($wc['account_name'] ? "\nChủ TK: " . tg_esc($wc['account_name']) : '')
                    . "\nNội dung: <code>" . Wallet::code($u, $sp) . "</code>\nTối thiểu: " . Wallet::money($wc['min']) . ($wc['has_api'] ? "\n⚡ Tiền tự cộng sau 1–2 phút." : '');
            }
            $pun = [];
            foreach (DB::all('SELECT m.*, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE g.active = 1 AND m.tg_user_id = ? AND ' . Space::groupsSql($sp, 'g') . ' AND ' . Amnesty::punishedSql('m'), [$from['id']]) as $m) {
                if ($p = Amnesty::punishments($m)) $pun[] = '• ' . tg_esc($m['title']) . ': ' . tg_esc(implode(', ', $p));
            }
            $ac = Amnesty::config($sp);
            if ($pun && $ac['enabled']) $t .= "\n\n🕊 <b>Có thể ân xá</b> (gỡ toàn bộ: " . Wallet::money($ac['price']) . ")\n" . implode("\n", $pun);
            $this->reply($msg, $t, $this->webButton('💳 Mở ví trên web', '/wallet' . ($sp ? "/$sp" : '')));
            return;
        }
        if ($key === 'pme') {
            $rows = DB::all('SELECT m.*, g.title FROM tg_members m JOIN tg_groups g ON g.id = m.group_id WHERE m.tg_user_id = ? ORDER BY m.points DESC LIMIT 20', [$from['id']]);
            if (!$rows) { $this->reply($msg, 'Bạn chưa có dữ liệu trong nhóm nào.'); return; }
            $lines = array_map(fn($r) => '• ' . tg_esc($r['title']) . ": <b>{$r['points']}</b> điểm, {$r['messages']} tin nhắn", $rows);
            $this->reply($msg, "⭐ <b>Điểm tương tác của bạn</b>\n\n" . implode("\n", $lines));
            return;
        }
        // Lệnh mặc định đã tắt hoặc lệnh lạ trong chat riêng: hiện lời chào/hướng dẫn
        $site = Settings::get('site_name');
        $this->reply($msg, "👋 Xin chào <b>" . tg_esc($from['first_name']) . "</b>!\nTôi là bot quản lý nhóm của <b>" . tg_esc($site) . "</b>.\n\n"
            . "➕ Thêm tôi vào nhóm và cấp quyền quản trị để bắt đầu.\n"
            . (Commands::names('login') ? '🔐 Đăng nhập web: ' . $c('login') . " MÃ (lấy mã ở trang đăng nhập)\n" : '')
            . (Commands::names('link') ? '🔗 Liên kết tài khoản web: ' . $c('link') . " MÃ\n" : '')
            . (Commands::names('pme') ? '⭐ Xem điểm: ' . $c('pme') . "\n" : '')
            . (Commands::names('balance') ? '💳 Số dư ví: ' . $c('balance') . "\n" : '')
            . (GroupFeature::anyEnabled('learn') ? '📚 Học tập: ' . $c('learn') . " hoặc bấm nút bên dưới\n" : '')
            . "\nID của bạn: <code>{$from['id']}</code>", ['reply_markup' => BotKeyboard::markup() ?: ['remove_keyboard' => true]]);
    }

    // ---------- Tiện ích ----------
    private function reply(array $msg, string $text, array $extra = []): void
    {
        $this->api->trySend($msg['chat']['id'], $text, ['reply_parameters' => ['message_id' => $msg['message_id'], 'allow_sending_without_reply' => true]] + $extra);
    }

    /** Nút mở 1 trang trên web (chỉ khi web có https, Telegram mới chấp nhận) */
    private function webButton(string $label, string $path): array
    {
        $url = site_url();
        if (!str_starts_with($url, 'https://')) return [];
        return ['reply_markup' => ['inline_keyboard' => [[['text' => $label, 'url' => $url . '/#' . $path]]]]];
    }

    /** Lệnh /diemdanh trong nhóm: điểm danh ngay cho người gõ, kèm nút mở trang điểm danh trên web */
    private function checkinCmd(array $group, array $msg, array $member): void
    {
        $c = Checkin::config($group);
        if (!$c['enabled'] || !Checkin::applies($c, (int)$group['id'])) { $this->reply($msg, '📅 Tính năng điểm danh chưa được bật ở nhóm này.'); return; }
        $btn = $this->webButton('📅 Điểm danh tại đây', '/checkin');
        try {
            $r = Checkin::run($group, $member, 'telegram');
            $text = "📅 " . Repo::mention($member) . " điểm danh thành công: <b>+{$r['points']} điểm</b>\n🔥 Chuỗi: <b>{$r['streak']} ngày</b> · Tổng: <b>{$r['total']}</b> điểm\n⏭ Ngày mai điểm danh tiếp: +{$r['next']} điểm";
        } catch (HttpError $e) {
            if ($e->status === 409) {
                $st = Checkin::streak($member['tg_user_id'], (int)$group['id']);
                $text = '✅ ' . Repo::mention($member) . " hôm nay đã điểm danh rồi (chuỗi {$st['streak']} ngày 🔥).\nQuay lại vào ngày mai để nhận <b>+" . Checkin::reward($c, $st['streak'] + 1) . ' điểm</b> nhé!';
            } else {
                $text = '📅 ' . tg_esc($e->getMessage());
            }
        }
        $sent = $this->api->trySend($msg['chat']['id'], $text, ['reply_parameters' => ['message_id' => $msg['message_id'], 'allow_sending_without_reply' => true]] + $btn);
        if ($c['cmd_autodel'] > 0) {
            DelQueue::add($this->api, (int)$this->bot['id'], $msg['chat']['id'], array_filter([$msg['message_id'], $sent['message_id'] ?? null]), (int)$c['cmd_autodel']);
        }
    }

    /** Tag @ tất cả quản trị viên (không tag bot, admin ẩn danh). Giới hạn 1 lần / 60 giây / nhóm. */
    private function callAdmins(array $group, array $msg, array $member, string $reason): void
    {
        $gid = (int)$group['id'];
        $chatId = $msg['chat']['id'];
        $last = (int)DB::val('SELECT last_at FROM tg_rule_hits WHERE rule_id = -5 AND group_id = ? AND tg_user_id = 0', [$gid]);
        if (now() - $last < 60) {
            $r = $this->api->trySend($chatId, '⏳ Quản trị viên vừa được gọi, vui lòng chờ ' . (60 - (now() - $last)) . ' giây nữa.', ['reply_parameters' => ['message_id' => $msg['message_id'], 'allow_sending_without_reply' => true]]);
            if (!empty($r['message_id'])) DelQueue::add($this->api, (int)$this->bot['id'], $chatId, [$r['message_id']], 15);
            return;
        }
        $list = $this->api->tryCall('getChatAdministrators', ['chat_id' => $chatId]);
        $tags = [];
        foreach (is_array($list) ? $list : [] as $a) {
            $u = $a['user'] ?? [];
            if (!empty($u['is_bot']) || !empty($a['is_anonymous'])) continue;
            $tags[] = !empty($u['username']) ? '@' . tg_esc($u['username'])
                : '<a href="tg://user?id=' . (int)$u['id'] . '">' . tg_esc(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: 'Admin') . '</a>';
        }
        if (!$tags) { $this->reply($msg, '⚠️ Không tìm thấy quản trị viên để gọi (bot cần là quản trị viên của nhóm).'); return; }
        DB::run('DELETE FROM tg_rule_hits WHERE rule_id = -5 AND group_id = ? AND tg_user_id = 0', [$gid]);
        DB::insert('tg_rule_hits', ['rule_id' => -5, 'group_id' => $gid, 'tg_user_id' => 0, 'last_at' => now()]);
        $reason = trim(mb_substr($reason, 0, 300));
        $target = $msg['reply_to_message'] ?? null;
        $t = '🚨 ' . Repo::mention($member) . ' đang gọi quản trị viên' . ($target ? ' (xem tin được reply)' : '') . ":\n" . implode(' ', $tags);
        if ($reason !== '') $t .= "\n📝 " . tg_esc($reason);
        // Trả lời vào tin bị báo (nếu có) để admin thấy ngay
        $this->api->trySend($chatId, $t, ['reply_parameters' => ['message_id' => $target['message_id'] ?? $msg['message_id'], 'allow_sending_without_reply' => true]]);
        Repo::log($gid, Repo::name($member), 'call_admin', $member, ($target ? 'Báo tin #' . $target['message_id'] . ' · ' : '') . ($reason !== '' ? $reason : 'Gọi admin'),
            ['source' => 'telegram', 'link' => $target ? Moderation::link($group, (int)$target['message_id']) : null], $target ? Moderation::evidence($target) : null);
    }

    /** Nội dung lệnh /anxa trong nhóm */
    /** /gdtg mua|ban @doitac 500k nội dung — tạo giao dịch trung gian (chỉ bot của chủ web) */
    private function escrowCmd(array $msg): void
    {
        if (Tenant::botOwner((int)$this->bot['id'])) return;
        $c = Escrow::config();
        $text = trim((string)$msg['text']);
        $help = "🤝 <b>GIAO DỊCH TRUNG GIAN</b>\nCú pháp: <code>/gdtg mua @nguoiban 500k nội dung</code>\nhoặc: <code>/gdtg ban @nguoimua 1.5tr nội dung</code>\n\n"
            . 'Phí: ' . rtrim(rtrim(number_format($c['fee_percent'], 2, ',', ''), '0'), ',') . '% (tối thiểu ' . Wallet::money($c['fee_min']) . ') · ' . mb_strtolower(Escrow::FEE_PAYER[$c['fee_payer']])
            . "\nBot tạo <b>phòng riêng</b> cho 2 bên + trung gian, giữ tiền đến khi người mua nhận hàng OK." . (str_starts_with(site_url(), 'https://') ? "\n🌐 Tạo trên web: " . site_url() . '/#/gdtg' : '');
        if (!$c['enabled']) { $this->reply($msg, '🤝 Giao dịch trung gian hiện chưa mở.'); return; }
        if (!preg_match('~^/gdtg(?:@\w+)?\s+(mua|bán|ban|buy|sell)\s+(@?[A-Za-z0-9_]{4,32}|\d{4,15})\s+(\S+)\s+(.+)$~isu', $text, $m)) { $this->reply($msg, $help); return; }
        $amount = Escrow::parseAmount($m[3]);
        if ($amount <= 0) { $this->reply($msg, '❌ Số tiền không hợp lệ (VD 500k, 1.5tr, 500000).'); return; }
        $role = in_array(mb_strtolower($m[1]), ['mua', 'buy'], true) ? 'buy' : 'sell';
        try {
            [$d, $link] = Escrow::create($msg['from'], $role, $m[2], $amount, $m[4], 'bot');
        } catch (HttpError $e) { $this->reply($msg, '❌ ' . tg_esc($e->getMessage())); return; }
        $t = "✅ Đã tạo <b>GDTG #{$d['id']}</b> · " . Wallet::money((int)$d['amount']) . ' · ' . tg_esc($d['description']) . "\n💸 Người mua chuyển: " . Wallet::money((int)$d['pay_total']) . ' · người bán nhận: ' . Wallet::money((int)$d['seller_gets']);
        $t .= $link ? "\n\n👉 <b>Vào phòng giao dịch:</b> $link\n(Gửi link này cho đối tác. Chỉ 2 bên được duyệt vào phòng.)" : "\n\n⏳ Hiện chưa có phòng trống, bot sẽ nhắn riêng cho bạn khi có phòng (hãy Start bot).";
        $this->reply($msg, $t, ['link_preview_options' => ['is_disabled' => true]]);
    }

    /** Không gian tiền của bot này: bot khách thuê → ví / ân xá / VIP của khách đó */
    private function space(): int
    {
        return Tenant::botOwner((int)$this->bot['id']) ?? 0;
    }

    private function amnestyText(array $group, array $msg): string
    {
        $sp = Space::ofGroup($group);
        $cfg = Amnesty::config($sp);
        if (!$cfg['enabled']) return '🕊 Tính năng ân xá chưa được bật.';
        $t = "🕊 <b>ÂN XÁ THÀNH VIÊN</b>\nDùng số dư ví trên web để gỡ <b>toàn bộ</b> hình phạt (cấm chat / cấm khỏi nhóm + cảnh cáo) cho mình hoặc người khác.\n\n💰 Giá ân xá: <b>" . Wallet::money($cfg['price']) . '</b>';
        $from = $msg['reply_to_message']['from'] ?? null;
        if ($from && empty($from['is_bot'])) {
            $m = Repo::member((int)$group['id'], $from['id']);
            $p = $m ? Amnesty::punishments($m, $cfg) : [];
            $t .= "\n\n👤 " . ($m ? Repo::mention($m) : tg_esc($from['first_name'] ?? '')) . ': ' . ($p ? tg_esc(implode(', ', $p)) : 'không có hình phạt nào.');
        }
        if ($cfg['note'] !== '') $t .= "\n\nℹ️ " . tg_esc($cfg['note']);
        $t .= "\n\n💳 Nạp tiền & ân xá tại: " . tg_esc(site_url()) . '/#/amnesty' . ($sp ? "/$sp" : '');
        return $t;
    }

    private function del($chatId, $messageId): void
    {
        $this->api->tryCall('deleteMessage', ['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    private function safe(callable $fn, ?array $msg = null)
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            $err = $e->getMessage();
            if (stripos($err, 'not enough rights') !== false || stripos($err, 'administrator') !== false) $err = 'Bot chưa đủ quyền quản trị trong nhóm.';
            if ($msg) $this->reply($msg, '❌ ' . tg_esc($err));
            return null;
        }
    }
}
