<?php

// namespace System\Support;

// use RecursiveIteratorIterator;
// use RecursiveDirectoryIterator;
// use DirectoryIterator;
// use FilesystemIterator;

class File
{
   /** @var array Files metadata entries: [name, ext, location] */
   private $files = [];
   /** @var string Normalized base path with forward slashes and trailing slash */
   private $basePath;

   private static $mimeMap = [
      // Images
      'jpg' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'png' => 'image/png',
      'gif' => 'image/gif',
      'webp' => 'image/webp',
      'svg' => 'image/svg+xml',
      'bmp' => 'image/bmp',
      'ico' => 'image/x-icon',
      // Documents
      'pdf' => 'application/pdf',
      'txt' => 'text/plain',
      'doc' => 'application/msword',
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'xls' => 'application/vnd.ms-excel',
      'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'ppt' => 'application/vnd.ms-powerpoint',
      'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      // Archives
      'zip' => 'application/zip',
      'rar' => 'application/x-rar-compressed',
      '7z' => 'application/x-7z-compressed',
      'tar' => 'application/x-tar',
      'gz' => 'application/gzip',
      // Audio
      'mp3' => 'audio/mpeg',
      'wav' => 'audio/wav',
      'flac' => 'audio/flac',
      'aac' => 'audio/aac',
      'ogg' => 'audio/ogg',
      'm4a' => 'audio/mp4',
      // Video
      'mp4' => 'video/mp4',
      'avi' => 'video/x-msvideo',
      'mkv' => 'video/x-matroska',
      'mov' => 'video/quicktime',
      'wmv' => 'video/x-ms-wmv',
      'webm' => 'video/webm',
      'flv' => 'video/x-flv',
      '3gp' => 'video/3gpp',
   ];

   public function __construct(array $files = [], string $basePath = '')
   {
      // normalisasi basePath ke forward slashes dan pastikan trailing slash
      $this->basePath = $this->normalizePath($basePath);
      if ($this->basePath !== '' && substr($this->basePath, -1) !== '/') {
         $this->basePath .= '/';
      }
      $this->files = $files;
   }


   /**
    * Dapatkan mime-type berdasarkan file path atau extension.
    * Jika extension tidak dikenali, akan mencoba finfo jika tersedia.
    */
   public static function getMimeType(string $file): string
   {
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

      // Prefer finfo when available for more accurate detection
      if (function_exists('finfo_open') && is_file($file)) {
         $finfo = finfo_open(FILEINFO_MIME_TYPE);
         if ($finfo !== false) {
            $mime = finfo_file($finfo, $file);
            finfo_close($finfo);
            if ($mime) return $mime;
         }
      }

      return self::$mimeMap[$ext] ?? 'application/octet-stream';
   }

   /**
    * Scan files di dalam direktori dengan opsi filter lanjutan.
    *
    * @param string $dir Direktori root untuk dipindai
    * @param string|array|null $extensionFilter Ekstensi tunggal atau array ekstensi (tanpa titik). null = semua
    * @param bool $recursive Apakah melakukan scan rekursif
    * @param array $options Opsi tambahan (see documentation)
    *   - namePattern: regex untuk nama file (tanpa ekstensi)
    *   - searchContent: string untuk dicari di isi file
    *   - contentRegex: regex untuk pencarian di isi file
    *   - caseSensitive: bool untuk pencarian teks
    *   - minSize/maxSize: batas ukuran bytes
    *   - modifiedAfter/modifiedBefore: timestamp or date string
    *   - maxReadBytes: max bytes to read for content search (default 5MB)
    *   - forceContentSearch: bool, baca file meskipun bukan text
    * @return self
    */
   public static function scanFiles(string $dir, $extensionFilter = 'php', bool $recursive = true, array $options = []): self
   {
      $files = [];

      if (!is_dir($dir)) {
         return new self([], $dir);
      }

      // Normalisasi ekstensi
      $exts = null;
      if ($extensionFilter !== null) {
         if (is_string($extensionFilter)) {
            $exts = [ltrim(strtolower($extensionFilter), '.')];
         } elseif (is_array($extensionFilter)) {
            $exts = array_map(function ($e) {
               return ltrim(strtolower($e), '.');
            }, $extensionFilter);
         }
      }

      // Opsi tambahan
      $namePattern = $options['namePattern'] ?? null; // regex
      $searchContent = $options['searchContent'] ?? null; // plain string
      $contentRegex = $options['contentRegex'] ?? null; // regex pattern
      $caseSensitive = $options['caseSensitive'] ?? false;
      $minSize = isset($options['minSize']) ? (int)$options['minSize'] : null; // bytes
      $maxSize = isset($options['maxSize']) ? (int)$options['maxSize'] : null; // bytes
      $modifiedAfter = $options['modifiedAfter'] ?? null; // timestamp or parsable date
      $modifiedBefore = $options['modifiedBefore'] ?? null; // timestamp or parsable date
      $maxReadBytes = isset($options['maxReadBytes']) ? (int)$options['maxReadBytes'] : 5 * 1024 * 1024; // 5MB default
      $forceContentSearch = $options['forceContentSearch'] ?? false; // force content search even for binary

      // normalize dates to timestamps if provided as string
      if ($modifiedAfter && !is_int($modifiedAfter)) {
         $modifiedAfter = strtotime((string)$modifiedAfter);
      }
      if ($modifiedBefore && !is_int($modifiedBefore)) {
         $modifiedBefore = strtotime((string)$modifiedBefore);
      }

      if ($recursive) {
         $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
         );
      } else {
         $iterator = new DirectoryIterator($dir);
      }

      foreach ($iterator as $file) {
         // DirectoryIterator yields string entries for '.' and '..' in some contexts
         if (is_string($file)) continue;

         if (!$file->isFile()) continue;

         $ext = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));

         if ($exts !== null && !in_array($ext, $exts, true)) {
            continue;
         }

         // namePattern filter (regex)
         if ($namePattern !== null) {
            $name = $file->getBasename("." . $ext);
            if (@preg_match($namePattern, '') === false) {
               // invalid regex - skip pattern matching
            } else {
               if (!preg_match($namePattern, $name)) {
                  continue;
               }
            }
         }

         $pathname = $file->getPathname();

         // size filters
         $fsize = $file->getSize();
         if ($minSize !== null && $fsize < $minSize) continue;
         if ($maxSize !== null && $fsize > $maxSize) continue;

         // mtime filters
         $mtime = $file->getMTime();
         if ($modifiedAfter !== null && $mtime < $modifiedAfter) continue;
         if ($modifiedBefore !== null && $mtime > $modifiedBefore) continue;

         // content search (only when requested)
         $contentMatched = true;
         if ($searchContent !== null || $contentRegex !== null) {
            $contentMatched = false;

            // safety: avoid reading very large files
            if ($fsize > 0 && $fsize <= $maxReadBytes) {
               // To avoid reading binary files accidentally, check mime or extension unless forced
               $allowRead = $forceContentSearch;
               if (!$allowRead) {
                  $mime = self::getMimeType($pathname);
                  if (strpos($mime, 'text/') === 0 || in_array($ext, ['php', 'js', 'css', 'html', 'htm', 'txt', 'md', 'json', 'xml'], true)) {
                     $allowRead = true;
                  }
               }

               if ($allowRead && is_readable($pathname)) {
                  $data = file_get_contents($pathname);
                  if ($data !== false) {
                     if ($contentRegex !== null) {
                        // regex match
                        if (@preg_match($contentRegex, '') === false) {
                           // invalid regex - fallback to false
                        } else {
                           if (preg_match($contentRegex, $data)) {
                              $contentMatched = true;
                           }
                        }
                     }

                     if (!$contentMatched && $searchContent !== null) {
                        if ($caseSensitive) {
                           if (strpos($data, $searchContent) !== false) $contentMatched = true;
                        } else {
                           if (stripos($data, $searchContent) !== false) $contentMatched = true;
                        }
                     }
                  }
               }
            }

            if (!$contentMatched) continue;
         }

         $files[] = [
            'name'     => $file->getBasename("." . $ext),
            'ext'      => $ext,
            'location' => $pathname,
            'size'     => $fsize,
            'mtime'    => $mtime,
         ];
      }

      return new self($files, $dir);
   }

   /** Helper: normalisasi path menggunakan forward slashes */
   private function normalizePath(string $p): string
   {
      $p = str_replace('\\', '/', $p);
      $p = rtrim($p, '/');
      return $p;
   }

   /** Kembalikan file list mentah */
   public function raw(): array
   {
      return $this->files;
   }

   /** Alias to raw */
   public function toArray(): array
   {
      return $this->raw();
   }

   /** Cek apakah path ada */
   public function exists(string $path): bool
   {
      $abs = $this->getAbsolutePath($path);
      return file_exists($abs);
   }

   /** Baca konten file */
   public function getContents(string $path)
   {
      $abs = $this->getAbsolutePath($path);
      return is_file($abs) ? file_get_contents($abs) : false;
   }

   /** Tulis konten ke file, membuat direktori bila perlu. Mengembalikan jumlah byte yang ditulis atau false */
   public function putContents(string $path, $data, $flags = 0)
   {
      $abs = $this->getAbsolutePath($path);
      $dir = dirname($abs);
      if (!is_dir($dir)) {
         mkdir($dir, 0755, true);
      }
      return file_put_contents($abs, $data, $flags);
   }

   /** Hapus file */
   public function delete(string $path): bool
   {
      $abs = $this->getAbsolutePath($path);
      if (is_file($abs)) {
         return unlink($abs);
      }
      return false;
   }

   /** Ukuran file dalam bytes */
   public function size(string $path)
   {
      $abs = $this->getAbsolutePath($path);
      return is_file($abs) ? filesize($abs) : false;
   }

   /** Ekstensi file tanpa titik */
   public static function extension(string $file): string
   {
      return strtolower(pathinfo($file, PATHINFO_EXTENSION));
   }

   /** Dapatkan mime type (instance wrapper ke getMimeType) */
   public function mimeType(string $file): string
   {
      $abs = $this->getAbsolutePath($file);
      return self::getMimeType($abs);
   }

   /** Dapatkan path absolut berdasarkan basePath (jika basePath diset) */
   public function getAbsolutePath(string $path): string
   {
      // jika path sudah absolut, kembalikan normalisasi
      $normalized = $this->normalizePath($path);
      if ($normalized === '') return $this->basePath;

      // Windows absolute drive letter atau unix leading slash
      if (preg_match('#^[A-Za-z]:/#', $normalized) || strpos($normalized, '/') === 0) {
         return $normalized;
      }

      // relatif terhadap basePath
      return $this->basePath . ltrim($normalized, '/');
   }

   /** Jika path absolut mengandung basePath, kembalikan relative path */
   public function getRelativePath(string $path): string
   {
      $abs = $this->getAbsolutePath($path);
      if ($this->basePath !== '' && strpos($abs, $this->basePath) === 0) {
         return ltrim(substr($abs, strlen($this->basePath)), '/');
      }
      return $abs;
   }
}
