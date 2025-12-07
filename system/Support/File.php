<?php
class File
{
   private static   $mimeMap = [
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


   public static function getMimeType(string $file): string
   {
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      return self::$mimeMap[$ext] ?? 'application/octet-stream';
   }
};
