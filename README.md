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
- **🖼 Xem ảnh / video / tệp trong tab Chat**: ảnh hiện thu nhỏ ngay trong khung chat, **bấm để xem to**, tải về; video / GIF / video tròn phát được, ghi âm & nhạc có trình phát, tệp có nút tải. Xem được cả ảnh của **tin đã bị xoá** (làm bằng chứng). File tải qua bot (không lộ token), lưu tạm 7 ngày trong `data/media`; tệp trên 20 MB Telegram không cho bot tải. Chỉ áp dụng cho tin gửi sau khi cập nhật 1.21.
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
- **😀 Lệnh kích hoạt bằng cảm xúc**: trong *Lệnh tự tạo* chọn KHI **Thả cảm xúc 👍**, chọn emoji (để trống = mọi cảm xúc). NẾU: ai được dùng (VD *chỉ admin*), điểm tối thiểu, thời gian chờ, *mỗi người 1 lần / tin*. THÌ: xoá tin được thả cảm xúc, cấm / cấm chat / cảnh cáo / đuổi chủ tin, cộng điểm, trả lời… Ví dụ: **admin thả 🤬 → xoá tin + cấm**, admin thả 😡 → cấm chat 1 ngày, thành viên thả ❤️ → chủ tin +1 điểm. Biến `{emoji}`. Bot phải là **quản trị viên** để nhận sự kiện cảm xúc (webhook tự đăng ký thêm sau khi cập nhật).
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
- **📢 Quảng cáo xoay vòng**: tạo chiến dịch gồm nhiều bài (ảnh + nội dung + nút bấm, xem trước giống Telegram). Bot đăng 1 bài và **ghim** lên nhóm, rồi **tự sửa tin đã ghim** sang bài kế tiếp mỗi 10 giây – 1 giờ (lần lượt hoặc ngẫu nhiên). **Hằng ngày** đúng giờ cài đặt (mặc định 00:00) đăng & ghim bài mới, bỏ ghim hoặc xoá bài cũ. Chọn **bot mặc định** của nhóm hoặc **thêm bot Telegram khác** (token) làm bot quảng cáo. Cần **cron mỗi 1 phút** (Shell Script `php /đường-dẫn/cron.php`)
- **⏰ Quảng cáo gửi theo lịch** (chiến dịch riêng, chọn khi tạo chiến dịch): bot **gửi tin mới** mỗi N phút (1 phút – 1 ngày, giới hạn khung giờ VD 08:00–23:00) hoặc vào **các giờ cố định** (VD 08:00, 12:30, 20:00); chọn ngày trong tuần. Tuỳ chọn: **gửi không tiếng**, **xoá bài cũ khi gửi bài mới**, ghim bài, **tự xoá sau N phút**, bài lần lượt / ngẫu nhiên, **bot quảng cáo riêng**. Bài có thể kèm ảnh hoặc **chỉ có chữ** (tối đa 4096 ký tự). Trang Quảng cáo hiện lần gửi trước / lần gửi tới của từng nhóm, có nút **Gửi ngay**.
- **🏷 Tag thành viên** (*Thêm → Tag thành viên*): đổi **tag** (nhãn hiện cạnh tên trong nhóm, tối đa 16 ký tự, không emoji – giới hạn Telegram) ở **Nhóm → Thành viên → Đổi tag**, lệnh admin **`/tag`** (reply tin rồi gõ `/tag VIP`, để trống = xoá), hành động **🏷 Đặt tag** trong Lệnh tự tạo (VD thả 🏆 → tag "Top Fan", dùng được biến), hoặc **tự động theo mốc điểm** (≥ 100 → "Tích cực", ≥ 500 → "VIP"…, không ghi đè tag admin đặt tay, nút áp dụng cho thành viên hiện có). Admin do bot bổ nhiệm → đổi danh hiệu admin. Tuỳ chọn báo vào nhóm, ghi nhật ký, đồng bộ khi tag bị đổi trong Telegram. Bot cần quyền **"Quản lý tag" (Manage tags)**.
- **🛒 Mua tag bằng số dư ví** (trang *Mua tag*, link ở Ví / Tài khoản; cài đặt ở *Tag thành viên → Cửa hàng tag*): thành viên chọn nhóm rồi mua **tag có sẵn** (admin đặt giá từng tag) hoặc **tự đặt chữ** (giá cố định, có **danh sách từ cấm** chống giả mạo admin / hỗ trợ). Tuỳ chọn **hạn dùng** (hết hạn cron tự gỡ, đổi sang tag theo điểm nếu bật), báo vào nhóm khi có người mua, chọn nhóm bán. Trừ tiền trước, Telegram lỗi thì **tự hoàn tiền**. Tag đã mua không bị tag tự động ghi đè. Admin nhóm không mua (dùng danh hiệu admin).
- **💱 Đổi điểm ↔ tiền** (trang *Đổi điểm*, cài đặt ở cuối trang): **🔒 điểm khoá = tổng điểm đã kiếm** (không giảm khi đổi, dùng xếp hạng / tag), **🔓 điểm mở = phần còn lại sau khi đổi** (= tổng đã kiếm − đã đổi). Chỉ điểm mở đổi được ra tiền ví theo tỉ giá admin đặt (VD 100 điểm = 1.000đ), có mức tối thiểu và giới hạn tiền mỗi ngày. Tuỳ chọn mua điểm bằng tiền ví (giá riêng). Admin đặt lại điểm thì điểm đã đổi về 0.
- **🎁 Mã quà tặng (giftcode)** (trang *Mã quà tặng*): admin tạo mã thưởng **tiền ví / điểm nhóm / ngày VIP**, giới hạn lượt dùng, hạn dùng, bật/tắt; mỗi người dùng 1 lần / mã, chống nhập sai liên tục. Thành viên nhập trên web hoặc gửi **`/code MÃ`** cho bot (mã thưởng tiền tự tạo tài khoản web từ Telegram nếu chưa có). **Điều kiện dùng mã** (tuỳ chọn): đang ở nhóm X, có ≥ N điểm / ≥ N tin nhắn ở nhóm đó, vào nhóm ≥ N ngày, đã nạp ví ≥ N đ, chỉ VIP, đã hoàn thành Học tập — không đạt thì báo rõ còn thiếu gì.
- **👑 Gói VIP** (trang *Gói VIP*): nhiều gói (VD 30 ngày 100.000đ, 90 ngày 250.000đ) mua bằng số dư ví, gia hạn cộng nối tiếp. Quyền lợi bật/tắt: **tag VIP**, **nhân x2 điểm** tin nhắn & điểm danh, **miễn điều kiện nhắn tin**, **miễn học tập**, **được gửi link**. Báo vào nhóm khi có VIP mới; hết hạn tự gỡ tag và nhắn riêng báo. Admin tặng / trừ ngày VIP theo ID, xem danh sách VIP và doanh thu.
- **📅 Điểm danh trên web** (trang *Điểm danh*, cài đặt ở cuối trang với admin web): thành viên chọn nhóm mình đang ở, bấm **Điểm danh** mỗi ngày 1 lần / nhóm để nhận **điểm tương tác của nhóm đó**. Điểm danh liên tiếp được **thưởng thêm** mỗi ngày (có giới hạn), bỏ 1 ngày là chuỗi về 0; hiện 7 ngày gần nhất. Trong nhóm gõ **`/diemdanh`** để điểm danh ngay (bot trả lời kèm nút **📅 Điểm danh tại đây** mở trang web, tự xoá lệnh + tin trả lời). Admin đặt điểm, thưởng chuỗi, nhóm áp dụng, tuỳ chọn báo vào nhóm (sửa nội dung, tự xoá). Ghi vào nhật ký (lọc *Điểm*).
- **🛡 Chống raid & tự duyệt thành viên** (*Thêm → Chống raid & duyệt thành viên*): bật *Phê duyệt thành viên mới* trong nhóm, bình thường **bot tự duyệt** yêu cầu tham gia ngay. Trong **X giây có ≥ N người** vào / xin vào (mặc định 5 người / 60 giây) → **khoá N phút**: ngừng tự duyệt (giữ chờ admin hoặc tự từ chối), người vào thẳng qua link / username bị cấm chat / đuổi / cấm (kể cả những người vừa vào kích hoạt), tuỳ chọn **đổi link mời chính**, **khoá chat cả nhóm** (hết hạn khôi phục quyền cũ), báo trong nhóm & nhắn riêng admin. Hết hạn tự mở lại. Trang web: trạng thái từng nhóm, khoá / mở tay, danh sách yêu cầu chờ **duyệt / từ chối** từng người hoặc tất cả. Người do admin thêm hoặc admin duyệt không bị xử lý.
- **🤖 Captcha khi vào nhóm** (*Thêm → Captcha khi vào nhóm*): người mới bị **tạm cấm chat**. Mặc định **xác minh qua bot**: trong nhóm bot chỉ **tag @username** (chưa có username thì tag tên) kèm nút **🤖 Xác minh qua bot**, câu hỏi gửi trong chat riêng nên nhóm không bị rối (vẫn có thể chọn xác minh ngay trong nhóm). Xác minh trong N giây bằng **bấm nút**, **giải phép tính** hoặc **chọn đúng emoji**. Đúng → mở chat (khôi phục quyền mặc định của nhóm), xoá tin xác minh, gửi lời chào của nhóm *sau khi* xác minh. Sai quá số lần / hết giờ → đuổi / cấm / giữ cấm chat. Bỏ qua người do admin thêm, người đã xác minh trước đây, VIP; tuỳ chọn xoá tin "đã tham gia", báo khi đuổi. Có xem trước và thống kê 7 ngày. Bot cần quyền cấm thành viên & xoá tin.
- **💎 Cho thuê (nhiều khách dùng chung web)** (*Thêm → Quản lý cho thuê*, trang `/admin`): bật *Mở cho thuê* để mọi tài khoản thấy trang **Gói thuê**. Khách đăng ký tài khoản, nạp ví, **tự mua / gia hạn gói** (cùng gói cộng dồn ngày, đổi gói tính từ hôm nay; gói 0đ = dùng thử, mỗi tài khoản 1 lần), rồi **thêm bot riêng** (token @BotFather) ở *Bot của tôi* và tự quản lý nhóm của bot đó: cài đặt nhóm, thành viên, nhật ký và các tính năng có trong gói. Gói giới hạn **số bot, số nhóm, tính năng** (lệnh tự tạo, chat, gửi tin, captcha, chống raid, cấm tên, yêu cầu username, đóng dấu ảnh, điểm danh) và **thời hạn**. Hết hạn / bị khoá / nhóm vượt số lượng → bot của khách tạm ngừng xử lý (báo 1 lần / ngày), cài đặt vẫn giữ. Nhắc gia hạn qua Telegram trước N ngày. Dữ liệu tách biệt: khách chỉ thấy bot / nhóm của mình; nhóm của khách không theo cài đặt chung, lệnh dùng chung, ví / ân xá / VIP / đổi điểm / mua tag của chủ web; bot khác không chiếm được nhóm đang có bot quản lý. Trang `/admin`: số liệu (khách đang thuê, sắp hết hạn, doanh thu), danh sách khách (gia hạn / đổi gói / tặng ngày / khoá), quản lý gói, đơn hàng.
- **🤝 Giao dịch trung gian (GDTG) với kho phòng** (*Thêm → Quản lý GDTG*): chủ web tạo sẵn các nhóm làm **phòng** (VD 10 phòng: bạn là chủ, thêm bot làm quản trị + các trung gian, ẩn lịch sử chat với người mới; có nút *Kiểm tra* điều kiện phòng). Thành viên tạo giao dịch bằng `/gdtg mua|ban @doitac 500k nội dung` hoặc trên web (*Giao dịch trung gian*): bot cấp **phòng trống**, đổi tên phòng, gửi **link xin vào chỉ duyệt người mua / người bán**, ghim thẻ giao dịch có nút theo từng bước (đồng ý → chuyển tiền kèm QR & nội dung `GDTG<mã>` → giao hàng → nhận hàng OK / khiếu nại). **Tiền do trung gian xác nhận thủ công** — chỉ các **ID Telegram đã cài** mới bấm được Xác nhận đã nhận tiền / Giải ngân / Hoàn tiền / Huỷ (trong phòng hoặc trên web); nếu có API ngân hàng, bot chỉ báo trung gian số tiền về để đối chiếu. Người bán gửi STK bằng `/stk`; giải ngân chuyển khoản tay hoặc cộng vào ví web. Phí theo % (tối thiểu / tối đa, người mua / người bán / chia đôi), giới hạn số tiền; hạn giờ chỉ **nhắc và báo trung gian**, chưa thanh toán (và chưa báo chuyển) quá hạn thì tự huỷ. Xong giao dịch: **lưu toàn bộ tin nhắn làm bằng chứng**, sau 5 phút mời 2 bên ra, thu hồi link, trả phòng trống và cấp ngay cho giao dịch đang chờ. Trang quản trị: số liệu (đang chạy, đang giữ tiền, khiếu nại, phí), danh sách & chi tiết giao dịch kèm tin nhắn, quản lý phòng, cài đặt. **Bot GDTG riêng**: thêm 1 bot (token riêng) ở trang *Bot*, rồi chọn ở *Quản lý GDTG → Cài đặt → Bot phụ trách GDTG* — bot này chỉ làm GDTG (/gdtg, phòng, nút, nhắn riêng cho người mua / bán / trung gian), không quản lý nhóm thông thường và không dùng cho đăng nhập / báo nạp tiền; bot quản lý nhóm bỏ qua phòng GDTG và chỉ người dùng sang bot GDTG khi gõ /gdtg. Phòng chỉ cần bot GDTG làm quản trị.
- **📨 Nhắn riêng thành viên qua bot** (*Thêm → Nhắn riêng thành viên*, quyền *Gửi tin vào nhóm*): chọn nhóm, soạn tin (định dạng HTML, biến `{name}` `{first_name}` `{username}` `{group}` thay theo từng người), kèm **ảnh** và **nút link**, gửi không tiếng, tuỳ chọn gửi cả người đã rời nhóm. Bot chỉ nhắn được người **đã Start bot** — hệ thống tự ghi nhận ai đã nhắn riêng cho bot, ai chặn / bỏ chặn bot; mỗi nhóm hiện *đã Start / thành viên* và có nút **📢 Mời Start** (bot đăng nút “Nhận thông báo riêng” vào nhóm). **Gửi nhanh ~25 tin/giây** (song song, gần giới hạn của Telegram), chạy nền (đóng trang vẫn gửi; cron gửi tiếp), tự chờ khi Telegram yêu cầu chậm lại, tự bỏ người đã chặn bot ở lần sau; ảnh tải lên 1 lần rồi dùng lại. Có **Gửi thử cho tôi**, xem trước, **tiến độ trực tiếp** (đã gửi / lỗi / tốc độ) và nút **dừng**. Khách thuê dùng được nếu gói có “Nhắn riêng thành viên”.
- **🔓 Yêu cầu mở band** (*Thêm → Yêu cầu mở band (cài đặt)*, có cài đặt riêng từng nhóm): thành viên vào trang **Xin mở band** (`/#/unban`), chọn nhóm, nhập **ID Telegram** (có hướng dẫn lấy ID: /start với bot của nhóm hoặc @userinfobot), **@username**, **lý do bị band** rồi gửi (gửi giúp người khác được). Bot kiểm tra ID đang bị band thật, chặn gửi trùng. **Admin nhóm nhận tin riêng qua bot** kèm nút ✅ Duyệt / ❌ Từ chối, và có trang **Yêu cầu mở band** trên web để duyệt (kèm ghi chú). Trong nhóm có **thông báo kèm link gửi yêu cầu** để mọi người biết chỗ xin mở band. Duyệt → bot gỡ band, gửi **link vào lại dùng 1 lần** cho người đó, báo trong nhóm, và gắn tag **“Từng Bị Band”** khi họ vào lại nhóm. Từ chối → nhắn lý do cho người đó.
- **🏪 Ví riêng của từng khách thuê**: mỗi khách (nếu gói có) tự cài **tài khoản ngân hàng + link API nạp tự động** ở mục *Thanh toán*; thành viên nhóm của khách nạp tiền vào tài khoản của khách, số dư nằm trong **ví riêng của khách** (không lẫn với ví của chủ web hay khách khác). Khách tự bật / cấu hình **ân xá, VIP, đổi điểm, mã quà tặng** cho nhóm của mình (*VIP / Đổi điểm / Mã quà cho nhóm tôi*), xem giao dịch ngân hàng, gán tay giao dịch không khớp, cộng / trừ tiền ví thành viên. Thành viên ở nhiều nơi thấy các ví dạng chip để chuyển qua lại (VD 🏠 web chính · 🏪 Shop ABC); lệnh /sodu, /anxa, /code trong bot của khách dùng ví của khách. Tính năng tiền bật theo gói (Ví & nạp tiền, Ân xá, VIP, Đổi điểm, Mã quà tặng); gói không có thì tự tắt.
- **⚙️ Cài đặt riêng từng nhóm** (trang nhóm → tab *⚙️ Tính năng*): Captcha, Chống raid, Cấm tên chứa từ cấm, Yêu cầu Username, Đóng dấu ảnh, Học tập, Điểm danh đều có **cài đặt chung** (admin web) và **cài đặt riêng cho từng nhóm**. Nhóm chưa có cài đặt riêng thì theo cài đặt chung; bấm *Lưu* ở trang tính năng của nhóm để tạo cài đặt riêng, *Dùng lại cài đặt chung* để bỏ. **Quản trị viên nhóm** (tài khoản web đã liên kết Telegram, có quyền *Cài đặt nhóm*) tự cài đặt cho nhóm của mình, không xem / sửa được nhóm khác. Trang cài đặt chung hiện danh sách nhóm đang dùng cài đặt riêng.
- **📦 Xuất / nhập cài đặt**: xuất toàn bộ cài đặt của 1 nhóm (cài đặt nhóm, các tính năng, lệnh riêng) thành file JSON để sao lưu hoặc nhập sang nhóm khác; xem trước và chọn mục trước khi nhập, lệnh trùng tên được bỏ qua. Cài đặt chung (tính năng, lệnh dùng chung, lệnh mặc định, bàn phím bot) xuất / nhập ở *Hệ thống*. File không chứa token bot, mật khẩu hay thông tin ví.
- **🚫 Cấm tên chứa từ cấm** (*Thêm → Cấm tên chứa từ cấm*): người có **tên hoặc @username chứa từ cấm** (VD “admin”, “hỗ trợ”, “cskh”) bị **cấm / đuổi / cấm chat** ngay khi vào nhóm, khi nhắn tin (bắt người đổi tên sau khi vào) và bị **từ chối yêu cầu tham gia**. So khớp thông minh chống lách: không dấu / có dấu, `A.d.m.i.n`, `4dm1n`, chữ kiểu `𝐀𝐝𝐦𝐢𝐧`, chữ Nga giả Latin, `HoTro247`, nhưng không bắt nhầm tên thật như “Hồ Trọng”. Tuỳ chọn chặn tên trùng / giả quản trị viên của nhóm, danh sách ID bỏ qua, chọn nhóm, báo vào nhóm. Có ô **thử tên** và nút **quét thành viên hiện có** để xử lý hàng loạt.
- **🖼 Đóng dấu ảnh chống copy** (*Thêm → Đóng dấu ảnh chống copy*): thành viên gửi ảnh → bot tải ảnh, in **chữ mờ** (mặc định: tên · @username · tên nhóm; tuỳ biến `{name}` `{username}` `{group}` `{id}` `{date}`, nhiều dòng), gửi lại kèm chú thích **“📷 Ảnh của @xxx gửi”** (giữ chú thích gốc, giữ reply / topic) rồi **xoá ảnh gốc**. Kiểu: lặp chéo khắp ảnh / góc / giữa; màu trắng hoặc đen có bóng; chỉnh độ đậm & cỡ chữ; xem trước trực tiếp (thử được với ảnh của bạn). Tuỳ chọn: cả ảnh gửi dạng tệp, bỏ qua admin / VIP, chọn nhóm. Gửi lỗi thì giữ nguyên ảnh gốc. Cần PHP **GD + FreeType** (phông DejaVu đã kèm sẵn) và bot có quyền xoá tin; album được gửi lại thành từng ảnh.
- **🔐 Điều kiện nhắn tin giữa các nhóm** (*Thêm → Điều kiện nhắn tin*, admin web): VD **cần ít nhất 50 điểm ở nhóm A mới nhắn được ở nhóm B**. Điều kiện: *điểm tương tác*, *số tin nhắn* (≥ mức tối thiểu) hoặc *là thành viên* nhóm khác; có thể tính ở chính nhóm đó. Chưa đạt → bot **xoá tin** + nhắc còn thiếu bao nhiêu (biến `{need}` `{have}` `{missing}` `{source}`…), kèm nút vào nhóm A (tự dùng link nhóm công khai hoặc link mời bạn dán), giới hạn tần suất nhắc, tự xoá tin nhắc, bỏ qua admin. Nhiều điều kiện cho 1 nhóm = phải đạt tất cả. Có ô **kiểm tra thử** 1 thành viên.
- **📚 Học tập bắt buộc** (*Thêm → Học tập bắt buộc*, admin web): thành viên **chưa hoàn thành bài học** mà nhắn trong nhóm → bot **xoá tin** và nhắc kèm nút mở bot. Trong bot có nút **📚 Học tập** trên bàn phím (**đổi tên được**, hoặc lệnh `/hoctap`, link `t.me/bot?start=learn`): bot hỏi lần lượt các câu do admin đặt, hiện **tiến độ** (Câu 2/5 🟩🟩⬜…). Trả lời **khớp đáp án** là qua (nhiều đáp án, khớp chính xác hoặc *có chứa*, không phân biệt hoa thường, có gợi ý khi sai). Học xong: nhắn chúc mừng trong bot + **thông báo trong nhóm** "xxx vừa hoàn thành bài học" (sửa được nội dung, bật/tắt, tự xoá). Chọn nhóm áp dụng, *tất cả thành viên chưa học* hoặc *chỉ thành viên mới*, bỏ qua admin. Tab Tiến độ: ai đã học / đang học / chưa học, **cho qua** hoặc **bắt học lại** theo ID.
- **⌨️ Bàn phím bot** (*Lệnh → ⌨️ Bàn phím*, admin web): tự thêm các nút nằm dưới ô nhập tin khi chat riêng với bot — đặt tên, sắp xếp, xếp nhiều nút cùng hàng (tối đa 4/hàng), chữ mờ trong ô nhập. Mỗi nút chọn 1 việc: **📚 Học tập**, **💳 Số dư ví**, **⭐ Điểm của tôi**, **👋 Lời chào**, **💬 Trả lời nội dung tự soạn** (kèm nút link tuỳ chọn, biến `{name}` `{username}` `{id}`), **🔗 Mở link** (web, kênh, nhóm…). Có xem trước giống Telegram.
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
| `@admin` hoặc `/admin [nội dung]` | Mọi người | Gọi admin: bot tag @ tất cả quản trị viên (bỏ qua bot, admin ẩn danh); reply tin vi phạm rồi gọi để admin thấy ngay tin đó; 1 lần / 60 giây / nhóm |
| `/anxa` (reply) | Mọi người | Giá ân xá, hình phạt của người được reply, nút mở trang Ân xá |
| `/warn [lý do]` `/unwarn` `/resetwarn` | Admin | Cảnh cáo (reply hoặc `@username` / ID) |
| `/mute [30m\|2h\|1d] [lý do]` `/unmute` | Admin | Cấm chat |
| `/kick` `/ban` `/unban` | Admin | Đuổi / cấm / gỡ cấm |
| `/addpoints 10 [lý do]` | Admin | Cộng (hoặc trừ, số âm) điểm |
| `/del` `/pin` `/unpin` `/setrules` `/reload` | Admin | Xoá / ghim tin, đặt nội quy, làm mới danh sách admin |

Chat riêng với bot: `/start`, `/login MÃ` (hoặc `/dangnhap`), `/link MÃ`, `/me`, `/sodu` (số dư ví, cách nạp tiền, hình phạt có thể ân xá).

**🔖 Yêu cầu đặt Username** (*Lệnh → Lệnh mặc định → Tính năng mặc định*): thành viên chưa có @username khi nhắn tin hoặc vừa vào nhóm sẽ bị *chỉ nhắc* / *xoá tin + nhắc* / *cấm chat cho tới khi đặt username* / *đuổi*. Tin nhắc có nút **✅ Tôi đã đặt username**: bot kiểm tra lại và tự bỏ cấm chat. Chọn nhóm áp dụng, bỏ qua admin, tự xoá tin nhắc, giới hạn tần suất, sửa nội dung (có xem trước).

**Tuỳ chỉnh lệnh mặc định** (*Lệnh → 📚 Lệnh mặc định*): xem chi tiết, cú pháp, ví dụ của từng lệnh; admin web **đổi tên / thêm tên khác** (VD `/me` → `/canhan`, tối đa 5 tên), giữ hoặc tắt tên gốc, bật/tắt lệnh, sửa mô tả trong menu Telegram. Menu "/", `/help` và hướng dẫn của bot tự dùng tên mới. Lệnh tự tạo trùng tên được ưu tiên (trang lệnh sẽ cảnh báo).

**💰 Ví VND & nạp tiền** (*Thêm → Nạp tiền & ân xá*, chỉ admin web): mỗi tài khoản web có số dư VND. Trang **Ví** hiện số tài khoản, mã QR VietQR và **nội dung chuyển khoản riêng** của từng người (VD `NAPTIEN12` = tiền tố + ID tài khoản).
- **Nạp tự động**: dán link API lịch sử giao dịch (VD web2m `https://api.web2m.com/historyapiacb/…`) vào ô *Link API*. Cron mỗi phút đọc giao dịch tiền vào, khớp nội dung → cộng tiền và nhắn Telegram báo người nạp. Người dùng bấm *Tôi đã chuyển khoản — kiểm tra ngay* để đồng bộ ngay. Chống cộng trùng theo mã giao dịch.
- Link API chứa thông tin đăng nhập ngân hàng: chỉ lưu trong database, **không bao giờ hiện lại** trên web và không nằm trong mã nguồn. Nút *Kiểm tra API* để thử kết nối (không cộng tiền).
- Giao dịch sai nội dung / dưới mức tối thiểu nằm ở tab *Giao dịch NH* để **cộng tay** cho đúng tài khoản hoặc bỏ qua. Admin cộng / trừ số dư ở *Tài khoản web*.

**🕊 Ân xá thành viên** (trang *Ân xá*): **1 giá duy nhất** (admin đặt, mặc định 50.000đ) để **gỡ toàn bộ hình phạt** đang có trong 1 lần: cấm chat hoặc cấm khỏi nhóm + xoá hết cảnh cáo. Ân xá cho mình hoặc cho người khác trong nhóm (tuỳ chọn). Bot thực hiện ngay trên Telegram, **thông báo trong nhóm** (sửa được nội dung, có biến `{member}` `{payer}` `{price}` `{kind}`…), nhắn riêng cho người được ân xá; gỡ cấm thì kèm **link mời dùng 1 lần**. Telegram báo lỗi → **tự hoàn tiền**. Mọi lượt ân xá được ghi vào Nhật ký và lịch sử ví.
- **Ân xá theo ID Telegram**: người bị cấm *trước khi có web* (chưa có trong danh sách) → chọn nhóm, nhập ID, bot hỏi Telegram trạng thái thật (bị cấm / cấm chat) rồi cho ân xá. Trước khi trừ tiền bot luôn kiểm tra lại trên Telegram, hình phạt đã được gỡ thì không trừ tiền.

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
