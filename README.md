# Tele Manager – Web quản lý nhóm Telegram bằng bot

Web PHP (chạy tốt trên **aaPanel**) để quản lý nhóm Telegram bằng bot: thành viên, điểm tương tác, cấm / mute / kick, cảnh cáo, bộ lọc chống spam, gửi tin… **tất cả thao tác trên web**, giao diện tối ưu cho điện thoại, và **cập nhật 1 chạm từ GitHub**.

## Tính năng

**Phân quyền 3 cấp**
| Vai trò | Ai | Được làm |
|---|---|---|
| **Admin web** | Tài khoản có vai trò *Admin web* | Toàn quyền, gồm các mục chỉ admin web: Bot, Tài khoản web, Hệ thống & cập nhật, lệnh dùng chung mọi nhóm, chế độ menu lệnh, đổi bot quản lý nhóm |
| **Quản trị viên nhóm** | Thành viên web đã liên kết Telegram **và** là admin của nhóm trên Telegram (tự nhận diện) | Chỉ các nhóm của mình, theo quyền admin web cấp ở **Hệ thống → Phân quyền**: xem chat, xử lý thành viên, chỉnh điểm, cài đặt nhóm, lệnh riêng của nhóm, gửi tin, xem nhật ký, thao tác nguy hiểm (mặc định tắt) |
| **Thành viên** | Tài khoản web thường | Xem điểm, hạng, trạng thái của mình và bảng xếp hạng |


**Quản trị viên (admin)**
- Tổng quan: thống kê tin nhắn 14 ngày, thành viên, số lượt xử lý, top tương tác, hoạt động gần đây
- **Bot**: thêm nhiều bot bằng token, bật/tắt, kiểm tra webhook, nút "Thêm vào nhóm" (tự xin quyền admin)
- **💬 Chat nhóm**: xem tin nhắn của nhóm ngay trên web (30 tin gần nhất, nút *Xem thêm* tải tin cũ hơn, tự cập nhật tin mới mỗi 4 giây, lưu 30 ngày)
  - Cạnh mỗi tin có nút xử lý nhanh: **Xoá tin · Cảnh cáo · Cấm chat · Cấm**, nút ⋯ để **Trả lời, Ghim, Đuổi, Cộng/trừ điểm, Xem hồ sơ**
  - Hiện tin bị bot xoá (kèm lý do vi phạm), tin ghim, tin sửa, người ra/vào nhóm; gửi tin/trả lời vào nhóm từ ô soạn tin
- **Nhóm**: nhóm tự xuất hiện khi thêm bot; đồng bộ, gửi tin (HTML, ghim, im lặng), cho bot rời nhóm, reset điểm
- **Thành viên**: tìm kiếm, lọc (admin, bị cảnh cáo, cấm chat, bị cấm, đã rời), sắp xếp theo điểm/tin nhắn/hoạt động
  - Hồ sơ: điểm, hạng, số tin, cảnh cáo, ngày vào, nhóm khác, lịch sử xử lý, ghi chú nội bộ
  - Hành động: **Cảnh cáo / Gỡ cảnh cáo / Cấm chat (chọn thời gian) / Bỏ cấm chat / Đuổi / Cấm / Gỡ cấm / Cộng-trừ điểm / Đặt điểm**
  - Thêm theo ID để cấm trước người chưa có trong danh sách
- **Cài đặt từng nhóm**: lời chào / tạm biệt (có biến `{mention} {name} {group}`…), nội quy, xoá tin hệ thống,
  chặn link (có danh sách tên miền cho phép), chặn forward, chống flood, từ cấm, khoá ảnh/video/sticker/GIF/tệp/âm thanh/bình chọn,
  giới hạn cảnh cáo + hành động tự động (mute/kick/ban), cấu hình điểm (điểm mỗi tin, giãn cách, độ dài tối thiểu, thưởng mời bạn)
- **Lệnh tự tạo** (Khi… Nếu… Thì…), dùng chung mọi nhóm hoặc riêng từng nhóm:
  - **Khi**: gõ `/lệnh`, tin chứa từ khoá, đúng nguyên câu, bắt đầu bằng, hoặc regex
  - **Nếu**: ai được dùng (mọi người / chỉ admin / không phải admin), điểm tối thiểu, bắt buộc reply, thời gian chờ mỗi người, tỉ lệ thành công %
  - **Thì** (nhiều hành động nối tiếp): trả lời, gửi tin, cộng/trừ điểm, xoá, ghim, cảnh cáo, cấm chat, đuổi, cấm, gỡ cấm. Mỗi hành động áp dụng cho người gửi hoặc người được reply
  - **52 biến** chia 6 nhóm, có giải thích, ví dụ và ô tìm kiếm; **xem trước tin nhắn** với giá trị mẫu ngay khi gõ:
    - Người gửi: `{mention} {name} {first_name} {last_name} {username} {id} {points} {rank} {messages} {warns} {warn_limit} {warns_left} {role} {joined} {days}`
    - Người được reply: `{target_mention} {target_name} {target_first_name} {target_username} {target_id} {target_points} {target_rank} {target_messages} {target_warns} {target_role} {target_days}`
    - Nhóm: `{group} {member_count} {messages_today} {rules} {top1} {top1_points} {top3} {group_link} {group_id} {bot}`
    - Chữ sau lệnh: `{args} {arg1} {arg2} {arg3}` · Thời gian: `{time} {date} {datetime} {weekday} {day} {month} {year}`
    - Ngẫu nhiên & khác: `{random} {random:1-6} {choice:Có|Không|Có thể} {count} {command}`
  - 21 mẫu dựng sẵn chia 3 nhóm: Điểm & giải trí (điểm danh, cảm ơn, quay số, xúc xắc, bói vui, thưởng điểm…), Thông tin & tự trả lời (nội quy, ID, chào lại, hỏi giá, liên hệ…), Quản trị (chặn từ khoá, chặn link mời, xử lý spam, cấm chat nhanh, cảnh cáo nhanh, ghim, dọn tin)
  - **Tự động xoá**: bật/tắt, đặt **thời gian riêng cho từng loại tin**: tin người gửi, tin bot trả lời, tin được reply (VD: người gửi xoá ngay, bot trả lời xoá sau 20 giây), từ ngay lập tức đến 2 ngày
  - Tự hiện trong menu lệnh của Telegram, lệnh tự tạo được ưu tiên hơn lệnh có sẵn nếu trùng tên
- **Thông báo xử lý chi tiết** trong nhóm: loại xử lý, thành viên + ID, thời hạn (đến mấy giờ), số cảnh cáo / còn bao nhiêu lần, lý do, **nội dung vi phạm** (tin bị reply hoặc tin bị bot xoá), **người xử lý** (qua Telegram / Web / Bot tự động / Lệnh tự tạo), thời gian. Tuỳ chỉnh ở *Cài đặt nhóm → Thông báo xử lý* (chi tiết/gọn, hiện người xử lý, trích nội dung, tự xoá tin vi phạm)
- **Nhật ký** mọi hành động, lưu đầy đủ chi tiết và nội dung vi phạm; bấm vào từng dòng để xem chi tiết và mở tin gốc trong Telegram (từ web, từ lệnh bot, tự động)
- **Tài khoản web**: tạo/sửa/khoá, phân quyền admin / thành viên
- **Hệ thống**: cài đặt chung, **Kiểm tra & Cập nhật từ GitHub**, link cron

**🔐 Đăng nhập bằng Telegram (không cần mật khẩu)**
- Trang đăng nhập → *Đăng nhập bằng Telegram* → web hiện mã 6 ký tự (hiệu lực 5 phút)
- Bấm *Mở bot* (mã tự gửi) hoặc nhắn `/login MÃ` cho bot trong chat riêng
- Bot hỏi xác nhận kèm thiết bị/IP → bấm ✅ → web tự đăng nhập
- Telegram chưa có tài khoản web sẽ được tự tạo (nếu đang cho phép đăng ký); có thể đặt mật khẩu sau ở trang Tài khoản
- Mã gắn với trình duyệt đã lấy mã; chỉ chính người gửi mã mới bấm xác nhận được. Bật/tắt ở *Hệ thống → Cài đặt chung*

**Thành viên (member)**
- Đăng ký tài khoản, **liên kết Telegram** bằng mã (bấm 1 nút mở bot)
- Xem tổng điểm, hạng, số tin, cảnh cáo, trạng thái cấm chat ở từng nhóm
- Bảng xếp hạng có bục vinh danh top 3

**Lệnh bot trong nhóm**

| Lệnh | Ai dùng | Tác dụng |
|---|---|---|
| `/help` `/rules` `/me` `/top` `/info` `/id` | Mọi người | Trợ giúp, nội quy, điểm của tôi, xếp hạng, thông tin, ID |
| `/warn [lý do]` `/unwarn` `/resetwarn` | Admin | Cảnh cáo (reply hoặc `@username` / ID) |
| `/mute [30m\|2h\|1d] [lý do]` `/unmute` | Admin | Cấm chat |
| `/kick` `/ban` `/unban` | Admin | Đuổi / cấm / gỡ cấm |
| `/addpoints 10 [lý do]` | Admin | Cộng (hoặc trừ, số âm) điểm |
| `/del` `/pin` `/unpin` `/setrules` `/reload` | Admin | Xoá / ghim tin, đặt nội quy, làm mới danh sách admin |

Chat riêng với bot: `/start`, `/link MÃ`, `/me`.

## Yêu cầu

- PHP **8.0+** (khuyên dùng 8.1–8.3) với `curl`, `mbstring`, `pdo_sqlite` **hoặc** `pdo_mysql`, `zip` (để tự cập nhật)
- Tên miền có **SSL (HTTPS)** – Telegram bắt buộc HTTPS cho webhook
- Database: **SQLite** (không cần cấu hình) hoặc **MySQL/MariaDB**

## Cài đặt trên aaPanel

1. **Website → Add site**: nhập tên miền, chọn PHP 8.x. (Nếu muốn MySQL, tạo database luôn ở bước này.)
2. **SSL**: vào site → *SSL* → *Let's Encrypt* → bật *Force HTTPS*.
3. **Đưa mã nguồn lên** thư mục site (vd. `/www/wwwroot/tenmien.com`):
   - Cách 1 – Terminal: `cd /www/wwwroot/tenmien.com && git clone https://github.com/duongit2795/betthumuaban.git .`
   - Cách 2 – *Files*: tải zip từ GitHub, upload và giải nén vào thư mục site.
4. **Quyền ghi**: *Files* → chọn thư mục site → *Permission* → owner `www`, quyền `755` (áp dụng cho thư mục con).
   Hoặc Terminal: `chown -R www:www /www/wwwroot/tenmien.com`
5. **Bảo vệ thư mục dữ liệu** (Nginx): site → *Config* → dán nội dung file `nginx.conf.example` vào trong khối `server { }` → Save.
   (Apache/OpenLiteSpeed đã có sẵn `.htaccess`.)
6. Nếu PHP thiếu extension: *App Store → PHP 8.x → Settings → Install extensions* → cài `zip`, `fileinfo` (nếu cần).
   Đồng thời ở *Disabled functions* **không cần** bật `exec`/`shell_exec` – web không dùng chúng.
7. Mở `https://tenmien.com` → trình cài đặt hiện ra → chọn SQLite hoặc nhập thông tin MySQL → tạo tài khoản admin.
8. Vào **Bot → Thêm bot**, dán token từ [@BotFather](https://t.me/BotFather). Web tự đặt webhook.
9. Trong BotFather: `/setprivacy` → chọn bot → **Disable** (để bot đọc tin nhắn và tính điểm).
10. Bấm **Thêm vào nhóm** ở thẻ bot, cấp quyền quản trị (xoá tin, cấm thành viên, ghim tin). Nhóm sẽ tự hiện trong mục **Nhóm**.
11. **Cron** (nên có): aaPanel → *Cron* → *Access URL* **mỗi 1 phút**, dán link ở trang **Hệ thống**. Cần cho tự động xoá tin hẹn trên 20 giây và dọn trạng thái cấm chat hết hạn.

## Cập nhật từ GitHub

1. Đẩy code mới lên GitHub (repo và nhánh khai báo ở **Hệ thống → Nguồn cập nhật**; mặc định `duongit2795/betthumuaban` nhánh `main`).
2. Vào **Hệ thống** → **Kiểm tra** (hiện danh sách thay đổi) → **Cập nhật**.

**Báo cập nhật qua Telegram:** web tự kiểm tra GitHub định kỳ (mặc định 6 giờ, đổi ở *Hệ thống → Báo cập nhật qua Telegram*). Khi có bản mới, bot nhắn riêng cho mọi admin web đã liên kết Telegram, kèm danh sách thay đổi và nút **✅ Cập nhật ngay / ⏭ Bỏ qua bản này / 🔗 Xem trên GitHub**. Bấm ✅ là web tự cập nhật, rồi báo kết quả ngay trong Telegram. Chỉ admin web bấm được. Admin cần đã nhắn `/start` cho bot.

Web tải file zip của nhánh qua GitHub API, chép đè mã nguồn và tự nâng cấp database.
Thư mục `data/` (cấu hình, database, phiên đăng nhập) **không bao giờ bị ghi đè**.
Repo riêng tư: nhập GitHub token (quyền *Contents: Read*) ở cùng trang.

## Cấu trúc

```
index.php        Giao diện (SPA)
api.php          API JSON cho giao diện
webhook.php      Nhận update từ Telegram (xác thực bằng secret token)
cron.php         Tác vụ định kỳ (tuỳ chọn)
app/             Mã PHP: DB, Auth, Telegram, BotHandler, Moderation, Updater, Api…
assets/          CSS, JS, icon
data/            Sinh ra khi cài: config.php, database SQLite, sessions (không commit)
```

## Xử lý sự cố

- **Bot không phản hồi**: Bot → *Kiểm tra*. Lỗi webhook thường do chưa có SSL hoặc sai "Địa chỉ web" ở trang Hệ thống.
- **Không tính điểm tin nhắn thường**: chưa tắt Privacy Mode trong BotFather.
- **"Bot chưa đủ quyền"**: cấp quyền quản trị cho bot trong nhóm rồi bấm *Đồng bộ* ở trang nhóm.
- **Cập nhật báo không ghi được tệp**: đặt owner `www` cho thư mục site (bước 4).
