<?php
defined('APP') or exit;

/**
 * Cập nhật mã nguồn từ GitHub bằng cách tải file zip của nhánh
 * và chép đè lên thư mục web. Không cần git/exec (phù hợp aaPanel).
 */
class Updater
{
    const VERSION_FILE = DATA_DIR . '/installed_version.json';
    // Không bao giờ ghi đè các đường dẫn này
    const PROTECTED = ['data', '.git', '.user.ini', '.htaccess.local'];

    public static function current(): array
    {
        $v = is_file(self::VERSION_FILE) ? (json_decode((string)file_get_contents(self::VERSION_FILE), true) ?: []) : [];
        return [
            'version' => APP_VERSION,
            'sha' => $v['sha'] ?? null,
            'updated_at' => $v['updated_at'] ?? null,
            'message' => $v['message'] ?? null,
        ];
    }

    private static function repo(): array
    {
        $repo = trim((string)Settings::get('update_repo'));
        $repo = preg_replace('~^https?://github\.com/~i', '', $repo);
        $repo = preg_replace('~\.git$~', '', trim($repo, '/'));
        if (!preg_match('~^[\w.-]+/[\w.-]+$~', $repo)) throw new HttpError('Repo GitHub không hợp lệ (dạng owner/repo)');
        $branch = trim((string)Settings::get('update_branch')) ?: 'main';
        return [$repo, $branch];
    }

    private static function http(string $url, ?string $saveTo = null)
    {
        $ch = curl_init($url);
        $headers = ['User-Agent: tele-manager-updater', 'Accept: application/vnd.github+json'];
        $tok = Settings::get('update_token');
        if ($tok) $headers[] = 'Authorization: Bearer ' . $tok;
        $opts = [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
        ];
        $fh = null;
        if ($saveTo) {
            $fh = fopen($saveTo, 'wb');
            $opts[CURLOPT_FILE] = $fh;
        } else {
            $opts[CURLOPT_RETURNTRANSFER] = true;
        }
        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($fh) fclose($fh);
        if ($res === false) throw new HttpError('Không kết nối được GitHub: ' . $err, 502);
        if ($code >= 400) {
            $msg = $code === 404 ? 'Không tìm thấy repo/nhánh (repo riêng tư cần token)' : "GitHub trả về lỗi $code";
            if ($code === 403) $msg = 'GitHub giới hạn truy cập (403), hãy thêm token';
            throw new HttpError($msg, 502);
        }
        return $saveTo ? true : json_decode((string)$res, true);
    }

    public static function check(): array
    {
        [$repo, $branch] = self::repo();
        $c = self::http("https://api.github.com/repos/$repo/commits/" . rawurlencode($branch));
        $cur = self::current();
        $latest = [
            'sha' => $c['sha'],
            'short' => substr($c['sha'], 0, 7),
            'message' => $c['commit']['message'] ?? '',
            'author' => $c['commit']['author']['name'] ?? '',
            'date' => isset($c['commit']['author']['date']) ? strtotime($c['commit']['author']['date']) : null,
            'url' => $c['html_url'] ?? null,
        ];
        $commits = [];
        if ($cur['sha'] && $cur['sha'] !== $latest['sha']) {
            try {
                $cmp = self::http("https://api.github.com/repos/$repo/compare/{$cur['sha']}..." . $latest['sha']);
                foreach (array_reverse($cmp['commits'] ?? []) as $cm) {
                    $commits[] = [
                        'short' => substr($cm['sha'], 0, 7),
                        'message' => strtok($cm['commit']['message'], "\n"),
                        'date' => strtotime($cm['commit']['author']['date']),
                    ];
                }
            } catch (Throwable $e) {}
        }
        return [
            'repo' => $repo,
            'branch' => $branch,
            'current' => $cur,
            'latest' => $latest,
            'has_update' => $cur['sha'] !== $latest['sha'],
            'commits' => array_slice($commits, 0, 30),
            'can_write' => is_writable(ROOT) && is_writable(ROOT . '/app'),
            'zip' => class_exists('ZipArchive'),
        ];
    }

    public static function update(): array
    {
        if (!class_exists('ZipArchive')) throw new HttpError('PHP thiếu extension zip. Vào aaPanel > PHP > Cài extension "zip"');
        @set_time_limit(300);
        [$repo, $branch] = self::repo();
        $info = self::http("https://api.github.com/repos/$repo/commits/" . rawurlencode($branch));
        $sha = $info['sha'];

        $tmp = DATA_DIR . '/update_' . bin2hex(random_bytes(4));
        @mkdir($tmp, 0755, true);
        $zipFile = $tmp . '/src.zip';
        $log = [];
        try {
            self::http("https://api.github.com/repos/$repo/zipball/$sha", $zipFile);
            $log[] = 'Đã tải bản ' . substr($sha, 0, 7) . ' (' . round(filesize($zipFile) / 1024) . ' KB)';
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) throw new HttpError('File zip tải về bị lỗi');
            $zip->extractTo($tmp . '/x');
            $zip->close();
            $dirs = glob($tmp . '/x/*', GLOB_ONLYDIR);
            if (!$dirs) throw new HttpError('Không tìm thấy mã nguồn trong file zip');
            $src = $dirs[0];
            if (!is_file($src . '/index.php') || !is_dir($src . '/app')) throw new HttpError('Bản tải về không đúng cấu trúc ứng dụng');

            $n = self::copyDir($src, ROOT);
            $log[] = "Đã cập nhật $n tệp";
            if (function_exists('opcache_reset')) @opcache_reset();
            file_put_contents(self::VERSION_FILE, json_encode([
                'sha' => $sha,
                'message' => strtok($info['commit']['message'] ?? '', "\n"),
                'updated_at' => now(),
            ], JSON_UNESCAPED_UNICODE));
            $log[] = 'Hoàn tất. Đang nâng cấp database...';
        } finally {
            self::rrmdir($tmp);
        }
        return ['sha' => $sha, 'log' => $log];
    }

    private static function copyDir(string $src, string $dst, string $rel = ''): int
    {
        $n = 0;
        foreach (scandir($src) as $f) {
            if ($f === '.' || $f === '..') continue;
            $r = ltrim($rel . '/' . $f, '/');
            if ($rel === '' && in_array($f, self::PROTECTED, true)) continue;
            $s = "$src/$f";
            $d = "$dst/$f";
            if (is_dir($s)) {
                if (!is_dir($d) && !@mkdir($d, 0755, true)) throw new HttpError("Không tạo được thư mục $r (kiểm tra quyền ghi)");
                $n += self::copyDir($s, $d, $r);
            } else {
                if (is_file($d) && md5_file($d) === md5_file($s)) continue;
                if (!@copy($s, $d)) throw new HttpError("Không ghi được tệp $r (kiểm tra quyền ghi của user www)");
                $n++;
            }
        }
        return $n;
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($dir);
    }
}
