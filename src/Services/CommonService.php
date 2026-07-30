<?php
namespace Common\Services;

use DateTime;
use DateTimeZone;
use Exception;

class CommonService {
    private $max_dim = 1920;

    private function getFileDirectory() {
        $config = require __DIR__ . '/../../config/settings.php';
        $dir_path = $config['timesheetpro_drive'] ?? '';
        if ($dir_path && substr($dir_path, -1) !== '/' && substr($dir_path, -1) !== '\\') {
            $dir_path .= '/';
        }
        return $dir_path;
    }

    private function getImageContextPath() {
        $config = require __DIR__ . '/../../config/settings.php';
        $path = $config['image_context_path'] ?? '';
        if ($path && substr($path, -1) !== '/') {
            $path .= '/';
        }
        return $path;
    }

    private function sanitizeFileName($filename) {
        return preg_replace('/[^a-zA-Z0-9\.\-]+/', '_', $filename);
    }

    private function validateExtension($filename) {
        $dot = strrpos($filename, ".");
        if ($dot === false) {
            throw new Exception("File has no extension");
        }
        $ext = strtolower(substr($filename, $dot + 1));

        $is_video = preg_match('/^(mp4|mkv|avi|mov)$/', $ext);
        $is_image = preg_match('/^(jpg|jpeg|png)$/', $ext);

        if (!$is_image && !$is_video) {
            throw new Exception(".$ext File type not supported");
        }
        return [$is_image, $is_video];
    }

    private function optimizeImage($input_file_path) {
        if (!extension_loaded('gd')) {
            return $input_file_path;
        }

        $info = getimagesize($input_file_path);
        if (!$info) {
            return $input_file_path;
        }

        $width = $info[0];
        $height = $info[1];
        $mime = $info['mime'];

        $max_side = max($width, $height);
        if ($max_side <= $this->max_dim) {
            return $input_file_path;
        }

        $scale = floatval($this->max_dim) / $max_side;
        $new_width = intval(round($width * $scale));
        $new_height = intval(round($height * $scale));

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $src = imagecreatefromjpeg($input_file_path);
                break;
            case 'image/png':
                $src = imagecreatefrompng($input_file_path);
                break;
            default:
                return $input_file_path;
        }

        if (!$src) {
            return $input_file_path;
        }

        $dst = imagecreatetruecolor($new_width, $new_height);

        if ($mime === 'image/png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        $path_parts = pathinfo($input_file_path);
        $out_file_path = $path_parts['dirname'] . '/opt_' . $path_parts['basename'];

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($dst, $out_file_path, 60);
                break;
            case 'image/png':
                imagepng($dst, $out_file_path);
                break;
        }

        imagedestroy($src);
        imagedestroy($dst);

        return $out_file_path;
    }

    public function start_upload($folder_name, $user_id, $file_name) {
        $last_dot = strrpos($file_name, ".");
        if ($last_dot === false) {
            throw new Exception("File has no extension");
        }

        $ext = strtolower(substr($file_name, $last_dot + 1));
        if (!preg_match('/^(jpg|jpeg|png)$/', $ext)) {
            throw new Exception("Only JPG, JPEG, PNG images are allowed");
        }

        $safe_file_name = $this->sanitizeFileName($file_name);
        // Generate a random UUID-like string
        $upload_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $file_dir = $this->getFileDirectory();
        $chunk_dir_path = $file_dir . ($user_id ?: "global") . "/tempImage/" . $folder_name . "/chunks/" . $upload_id;
        
        if (!file_exists($chunk_dir_path)) {
            mkdir($chunk_dir_path, 0777, true);
        }

        return [
            "uploadId" => $upload_id,
            "fileName" => $safe_file_name
        ];
    }

    public function upload_chunk($folder_name, $user_id, $upload_id, $chunk_index, $total_chunks, $original_file_name, $chunk_file) {
        $safe_name = $this->sanitizeFileName($original_file_name);
        $this->validateExtension($safe_name);

        $file_dir = $this->getFileDirectory();
        $chunk_dir_path = $file_dir . ($user_id ?: "global") . "/tempImage/" . $folder_name . "/chunks/" . $upload_id;

        if (!file_exists($chunk_dir_path)) {
            mkdir($chunk_dir_path, 0777, true);
        }

        $chunk_file_path = $chunk_dir_path . "/" . $chunk_index . ".part";
        
        if (!move_uploaded_file($chunk_file['tmp_name'], $chunk_file_path)) {
            throw new Exception("Failed to save chunk " . $chunk_index);
        }

        return [
            "chunkIndex" => $chunk_index,
            "totalChunks" => $total_chunks
        ];
    }

    public function complete_upload($folder_name, $user_id, $upload_id, $total_chunks, $original_file_name) {
        $safe_name = $this->sanitizeFileName($original_file_name);
        $this->validateExtension($safe_name);

        $file_dir = $this->getFileDirectory();
        $base_dir = $file_dir . ($user_id ?: "global") . "/tempImage/" . $folder_name;
        if (!file_exists($base_dir)) {
            mkdir($base_dir, 0777, true);
        }

        $chunk_dir_path = $base_dir . "/chunks/" . $upload_id;
        if (!file_exists($chunk_dir_path)) {
            throw new Exception("Chunk directory not found");
        }

        $final_file_path = $base_dir . "/" . $safe_name;
        if (file_exists($final_file_path)) {
            unlink($final_file_path);
        }

        // Merge chunks
        $merged_file = fopen($final_file_path, "ab");
        if (!$merged_file) {
            throw new Exception("Failed to open merged file for writing");
        }

        try {
            for ($i = 0; $i < $total_chunks; $i++) {
                $part_path = $chunk_dir_path . "/" . $i . ".part";
                if (!file_exists($part_path)) {
                    throw new Exception("Missing chunk: " . $i);
                }
                $part_file = fopen($part_path, "rb");
                if ($part_file) {
                    while (!feof($part_file)) {
                        fwrite($merged_file, fread($part_file, 8192));
                    }
                    fclose($part_file);
                }
            }
        } catch (Exception $e) {
            fclose($merged_file);
            throw new Exception("Failed to merge chunks: " . $e->getMessage());
        }
        fclose($merged_file);

        // Optimize image
        $optimized_path = $this->optimizeImage($final_file_path);
        if ($optimized_path !== $final_file_path) {
            unlink($final_file_path);
            rename($optimized_path, $final_file_path);
        }

        // Clean up chunk folder
        $this->delete_directory_recursively($chunk_dir_path);

        // Build URL
        $context_path = $this->getImageContextPath();
        $rel_url = ($user_id ?: 'global') . "/tempImage/" . $folder_name . "/" . $safe_name;
        $file_url = $context_path . $rel_url;

        return [
            "uploadedFiles" => [
                [
                    "imageName" => $safe_name,
                    "imageURL" => $file_url
                ]
            ]
        ];
    }

    public function upload_files($files, $login_user_id, $folder_name) {
        $uploaded_files = [];
        try {
            $file_dir = $this->getFileDirectory();
            $dynamic_path = ($login_user_id ?: 'global') . "/tempImage/" . $folder_name . "/";
            $target_dir = $file_dir . $dynamic_path;

            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $normalized_files = [];
            if (isset($files['name']) && is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $normalized_files[] = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                }
            } elseif (isset($files['name'])) {
                $normalized_files[] = $files;
            } else {
                $normalized_files = $files;
            }

            foreach ($normalized_files as $file) {
                $original_filename = $this->sanitizeFileName($file['name']);
                list($is_image, $is_video) = $this->validateExtension($original_filename);

                $full_path = $target_dir . $original_filename;
                if (!move_uploaded_file($file['tmp_name'], $full_path)) {
                    throw new Exception("Failed to move uploaded file.");
                }

                $context_path = $this->getImageContextPath();
                $file_url = $context_path . $dynamic_path . $original_filename;

                $uploaded_files[] = [
                    "imageName" => $original_filename,
                    "imageURL" => $file_url,
                    "fileType" => $is_video ? "video" : "image"
                ];
            }

            return [
                "uploadedFiles" => $uploaded_files,
                "status" => 200
            ];
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    public function update_file_location_for_profile($image, $login_user_id, $folder_name) {
        $arr = explode("/", $image);
        $original_file_name = end($arr);

        $file_dir = $this->getFileDirectory();
        $temp_dir = $file_dir . ($login_user_id ?: "global") . "/tempImage/" . $folder_name;
        $dest_dir = $file_dir . ($login_user_id ?: "global") . "/" . $folder_name;

        if (!file_exists($dest_dir)) {
            mkdir($dest_dir, 0777, true);
        }

        $source_file = $temp_dir . "/" . $original_file_name;
        $destination_file = $dest_dir . "/" . $original_file_name;

        if (file_exists($source_file)) {
            try {
                copy($source_file, $destination_file);
                $image_dynamic_path = ($login_user_id ?: 'global') . "/" . $folder_name . "/" . $original_file_name;
                $this->delete_directory_recursively($temp_dir);

                $context_path = $this->getImageContextPath();
                return $context_path . $image_dynamic_path;
            } catch (Exception $e) {
                throw new Exception("File move error: " . $e->getMessage());
            }
        } else {
            return "Error";
        }
    }

    public function delete_directory_recursively($directory_path) {
        if (file_exists($directory_path)) {
            $files = array_diff(scandir($directory_path), ['.', '..']);
            foreach ($files as $file) {
                $path = $directory_path . '/' . $file;
                (is_dir($path)) ? $this->delete_directory_recursively($path) : unlink($path);
            }
            return rmdir($directory_path);
        }
        return false;
    }

    public function convert_string_to_date($date_str) {
        if (!$date_str || !trim($date_str)) {
            return null;
        }

        $date_str = trim($date_str);

        // Handle ISO strings like 2026-01-31T08:34:45.622Z
        if (strpos($date_str, 'T') !== false) {
            if (substr($date_str, -1) === 'Z') {
                $date_str = substr($date_str, 0, -1) . '+00:00';
            }
            try {
                // Parse fractional second ISO formats
                $parts = explode('.', $date_str);
                if (count($parts) > 1 && strpos($parts[1], '+') !== false) {
                    $subparts = explode('+', $parts[1]);
                    if (strlen($subparts[0]) > 6) {
                        $parts[1] = substr($subparts[0], 0, 6) . '+' . $subparts[1];
                        $date_str = $parts[0] . '.' . $parts[1];
                    }
                }
                return new DateTime($date_str);
            } catch (Exception $e) {
                // Fall through
            }
        }

        $formats = [
            "d/m/Y, h:i:s A",
            "d/m/Y, H:i:s",
            "d/m/Y",
            "Y-m-d"
        ];

        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $date_str);
            if ($dt !== false) {
                $dt->setTimezone(new DateTimeZone('UTC'));
                return $dt;
            }
        }

        throw new Exception("Error converting date: " . $date_str . " - format not matched");
    }

    public function convert_utc_to_local($utc_time, $time_zone) {
        if (!$utc_time) {
            return null;
        }
        try {
            $has_ampm = (stripos($utc_time, 'am') !== false || stripos($utc_time, 'pm') !== false);
            $fmt = $has_ampm ? "d/m/Y, h:i:s A" : "d/m/Y, H:i:s";
            
            $dt = DateTime::createFromFormat($fmt, $utc_time, new DateTimeZone('UTC'));
            if (!$dt) {
                return null;
            }
            
            $dt->setTimezone(new DateTimeZone($time_zone));
            return $dt->format("d/m/Y, h:i:s A");
        } catch (Exception $e) {
            return null;
        }
    }

    public function convert_local_to_utc($local_date, $time_zone, $has_time) {
        try {
            if ($has_time && strpos($local_date, ':') !== false) {
                $fmt = "d/m/Y, H:i:s";
                $dt = DateTime::createFromFormat($fmt, $local_date, new DateTimeZone($time_zone));
                if (!$dt) {
                    throw new Exception("Failed to parse date");
                }
                $dt->setTimezone(new DateTimeZone('UTC'));
                return $dt;
            } else {
                $fmt = "d/m/Y";
                $dt = DateTime::createFromFormat($fmt, $local_date, new DateTimeZone($time_zone));
                if (!$dt) {
                    throw new Exception("Failed to parse date");
                }
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new DateTimeZone('UTC'));
                return $dt;
            }
        } catch (Exception $e) {
            throw new Exception("Error converting local date time to UTC: " . $e->getMessage());
        }
    }

    public function convert_date_to_string($date_obj, $time_zone = "UTC") {
        if (!$date_obj) {
            return null;
        }
        try {
            if (!($date_obj instanceof DateTime)) {
                if (is_string($date_obj)) {
                    $date_obj = new DateTime($date_obj);
                    return $date_obj->format("d/m/Y");
                }
                return null;
            }
            $date_obj->setTimezone(new DateTimeZone($time_zone));
            return $date_obj->format("d/m/Y, h:i:s A");
        } catch (Exception $e) {
            return null;
        }
    }

    public function send_email($to_email, $subject, $body) {
        $config = require __DIR__ . '/../../config/settings.php';

        $headers = [
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=utf-8',
            'From' => $config['mail_from_name'] . ' <' . $config['mail_from'] . '>',
            'Reply-To' => $config['mail_from']
        ];

        $headers_str = "";
        foreach ($headers as $k => $v) {
            $headers_str .= "$k: $v\r\n";
        }

        try {
            return mail($to_email, $subject, $body, $headers_str);
        } catch (Exception $e) {
            return false;
        }
    }
}
