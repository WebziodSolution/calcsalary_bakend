<?php
namespace Common\Services;

use Common\Models\UserInOut;
use Common\Models\CompanyEmployee;
use Common\Models\CompanyDetails;
use Common\Models\Locations;
use Common\Models\WeeklyOff;
use Common\Models\HolidayTemplates;
use Common\Models\HolidayTemplateDetails;
use Common\Models\CompanyShift;
use Common\Models\Department;
use Common\Services\CommonService;
use Common\Services\DbHelper;
use DateTime;
use DateTimeZone;
use DateInterval;
use PDO;
use Exception;

class UserInOutService {
    private $common_service;

    public function __construct() {
        $this->common_service = new CommonService();
    }

    private function resolve_time_zone($time_zone) {
        $normalized = trim((string)($time_zone ?? ''));
        if ($normalized === '') {
            return 'UTC';
        }

        $aliases = [
            'Asia/Calcutta' => 'Asia/Kolkata',
            'asia/calcutta' => 'Asia/Kolkata',
            'IST' => 'Asia/Kolkata'
        ];

        if (isset($aliases[$normalized])) {
            $normalized = $aliases[$normalized];
        }

        try {
            new DateTimeZone($normalized);
            return $normalized;
        } catch (Exception $e) {
            try {
                new DateTimeZone('Asia/Kolkata');
                return 'Asia/Kolkata';
            } catch (Exception $e2) {
                return 'UTC';
            }
        }
    }

    public function format_total_time($total_minutes) {
        $hours = (int)($total_minutes / 60);
        $minutes = $total_minutes % 60;
        return sprintf("%d hr %02d min", $hours, $minutes);
    }

    public function parse_date_string($date_str) {
        if ($date_str === null) {
            return null;
        }

        $date_str = trim((string)$date_str);
        if ($date_str === '') {
            return null;
        }

        if (strpos($date_str, ",") !== false) {
            $parts = explode(",", $date_str);
            $date_str = trim($parts[0]);
        }

        $formats = [
            '!d/m/Y',
            '!Y-m-d',
            'd/m/Y',
            'Y-m-d'
        ];

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $date_str);
            if ($dt instanceof DateTime) {
                $dt->setTime(0, 0, 0);
                return $dt;
            }
        }

        return null;
    }

    public function is_weekly_off_day(DateTime $date_obj, $weekly_off) {
        if ($weekly_off === null) {
            return false;
        }

        $day_of_week = (int)$date_obj->format('N'); // 1 (Mon) to 7 (Sun)
        $day_of_month = (int)$date_obj->format('j');
        $week_of_month = (int)ceil($day_of_month / 7);

        if ($day_of_week === 7) {  // SUNDAY
            if ($weekly_off->sundayAll) return true;
            return ($week_of_month === 1 && $weekly_off->sunday1st) ||
                   ($week_of_month === 2 && $weekly_off->sunday2nd) ||
                   ($week_of_month === 3 && $weekly_off->sunday3rd) ||
                   ($week_of_month === 4 && $weekly_off->sunday4th) ||
                   ($week_of_month === 5 && $weekly_off->sunday5th);
        } else if ($day_of_week === 1) {  // MONDAY
            if ($weekly_off->mondayAll) return true;
            return ($week_of_month === 1 && $weekly_off->monday1st) ||
                   ($week_of_month === 2 && $weekly_off->monday2nd) ||
                   ($week_of_month === 3 && $weekly_off->monday3rd) ||
                   ($week_of_month === 4 && $weekly_off->monday4th) ||
                   ($week_of_month === 5 && $weekly_off->monday5th);
        } else if ($day_of_week === 2) {  // TUESDAY
            if ($weekly_off->tuesdayAll) return true;
            return ($week_of_month === 1 && $weekly_off->tuesday1st) ||
                   ($week_of_month === 2 && $weekly_off->tuesday2nd) ||
                   ($week_of_month === 3 && $weekly_off->tuesday3rd) ||
                   ($week_of_month === 4 && $weekly_off->tuesday4th) ||
                   ($week_of_month === 5 && $weekly_off->tuesday5th);
        } else if ($day_of_week === 3) {  // WEDNESDAY
            if ($weekly_off->wednesdayAll) return true;
            return ($week_of_month === 1 && $weekly_off->wednesday1st) ||
                   ($week_of_month === 2 && $weekly_off->wednesday2nd) ||
                   ($week_of_month === 3 && $weekly_off->wednesday3rd) ||
                   ($week_of_month === 4 && $weekly_off->wednesday4th) ||
                   ($week_of_month === 5 && $weekly_off->wednesday5th);
        } else if ($day_of_week === 4) {  // THURSDAY
            if ($weekly_off->thursdayAll) return true;
            return ($week_of_month === 1 && $weekly_off->thursday1st) ||
                   ($week_of_month === 2 && $weekly_off->thursday2nd) ||
                   ($week_of_month === 3 && $weekly_off->thursday3rd) ||
                   ($week_of_month === 4 && $weekly_off->thursday4th) ||
                   ($week_of_month === 5 && $weekly_off->thursday5th);
        } else if ($day_of_week === 5) {  // FRIDAY
            if ($weekly_off->fridayAll) return true;
            return ($week_of_month === 1 && $weekly_off->friday1st) ||
                   ($week_of_month === 2 && $weekly_off->friday2nd) ||
                   ($week_of_month === 3 && $weekly_off->friday3rd) ||
                   ($week_of_month === 4 && $weekly_off->friday4th) ||
                   ($week_of_month === 5 && $weekly_off->friday5th);
        } else if ($day_of_week === 6) {  // SATURDAY
            if ($weekly_off->saturdayAll) return true;
            return ($week_of_month === 1 && $weekly_off->saturday1st) ||
                   ($week_of_month === 2 && $weekly_off->saturday2nd) ||
                   ($week_of_month === 3 && $weekly_off->saturday3rd) ||
                   ($week_of_month === 4 && $weekly_off->saturday4th) ||
                   ($week_of_month === 5 && $weekly_off->saturday5th);
        }
        return false;
    }

    public function dashboard_counts($company_id) {
        try {
            $db = DbHelper::getDb();
            
            $tz = new DateTimeZone("Asia/Kolkata");
            $now = new DateTime("now", $tz);
            
            $start_of_day = (clone $now)->setTime(0, 0, 0)->setTimezone(new DateTimeZone("UTC"))->format('Y-m-d H:i:s');
            $end_of_day = (clone $now)->setTime(23, 59, 59)->setTimezone(new DateTimeZone("UTC"))->format('Y-m-d H:i:s');
            
            $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as cnt FROM user_inout WHERE company_id = :comp_id AND time_in >= :start_day AND time_in <= :end_day");
            $stmt->execute(['comp_id' => $company_id, 'start_day' => $start_of_day, 'end_day' => $end_of_day]);
            $count_checked_in = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

            $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as cnt FROM user_inout WHERE company_id = :comp_id AND time_out >= :start_day AND time_out <= :end_day");
            $stmt->execute(['comp_id' => $company_id, 'start_day' => $start_of_day, 'end_day' => $end_of_day]);
            $count_checked_out = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM company_employees WHERE company_id = :comp_id");
            $stmt->execute(['comp_id' => $company_id]);
            $total_users = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

            $stmt = $db->prepare("
                SELECT DISTINCT ce.id, 
                       CONCAT(ce.first_name, CASE WHEN ce.middle_name IS NOT NULL AND TRIM(ce.middle_name) != '' THEN CONCAT(' ', TRIM(ce.middle_name)) ELSE '' END, ' ', ce.last_name) AS fullname 
                FROM user_inout uio 
                JOIN company_employees ce ON uio.user_id = ce.id 
                WHERE uio.company_id = :comp_id 
                  AND uio.time_in >= :start_day 
                  AND uio.time_in <= :end_day
            ");
            $stmt->execute(['comp_id' => $company_id, 'start_day' => $start_of_day, 'end_day' => $end_of_day]);
            $in_users_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT DISTINCT ce.id, 
                       CONCAT(ce.first_name, CASE WHEN ce.middle_name IS NOT NULL AND TRIM(ce.middle_name) != '' THEN CONCAT(' ', TRIM(ce.middle_name)) ELSE '' END, ' ', ce.last_name) AS fullname 
                FROM user_inout uio 
                JOIN company_employees ce ON uio.user_id = ce.id 
                WHERE uio.company_id = :comp_id 
                  AND uio.time_out >= :start_day 
                  AND uio.time_out <= :end_day
            ");
            $stmt->execute(['comp_id' => $company_id, 'start_day' => $start_of_day, 'end_day' => $end_of_day]);
            $out_users_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT id, 
                       CONCAT(first_name, CASE WHEN middle_name IS NOT NULL AND TRIM(middle_name) != '' THEN CONCAT(' ', TRIM(middle_name)) ELSE '' END, ' ', last_name) AS fullname 
                FROM company_employees 
                WHERE company_id = :comp_id                 
            ");
            $stmt->execute(['comp_id' => $company_id]);
            $total_users_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "countCheckedInUsers" => $count_checked_in,
                "countCheckedOutUsers" => $count_checked_out,
                "companyTotalUserCount" => $total_users,
                "inUsersData" => $in_users_data,
                "outUserData" => $out_users_data,
                "totalUserData" => $total_users_data
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function format_minutes_to_hhmm($minutes) {
        $hours = (int)($minutes / 60);
        $mins = $minutes % 60;
        return sprintf("%02d:%02d", $hours, $mins);
    }

    public function get_all_records_grouped_by_user($user_ids, $start_date_str, $end_date_str, $time_zone, $location_ids, $department_ids, $company_id) {
        try {
            $zone = new DateTimeZone($this->resolve_time_zone($time_zone));
            $utc_zone = new DateTimeZone("UTC");

            if (!$start_date_str || !$end_date_str) {
                $now = new DateTime("now", $utc_zone);
                $start_local = DateTime::createFromFormat("Y-m-d", $now->format("Y-m-01"));
                $end_local = clone $now;
                
                $start_zdt = new DateTime($start_local->format("Y-m-d 00:00:00"), $utc_zone);
                $end_zdt = new DateTime($end_local->format("Y-m-d 23:59:59"), $utc_zone);
            } else {
                $start_local = $this->parse_date_string($start_date_str);
                $end_local = $this->parse_date_string($end_date_str);
                
                $start_zdt = new DateTime($start_local->format("Y-m-d 00:00:00"), $zone);
                $end_zdt = new DateTime($end_local->format("Y-m-d 23:59:59"), $zone);
            }

            $start_utc = clone $start_zdt;
            $start_utc->setTimezone($utc_zone);
            $end_utc = clone $end_zdt;
            $end_utc->setTimezone($utc_zone);

            $where = "created_on >= :start_utc AND created_on <= :end_utc";
            $params = [
                "start_utc" => $start_utc->format('Y-m-d H:i:s'),
                "end_utc" => $end_utc->format('Y-m-d H:i:s')
            ];

            if (!empty($user_ids)) {
                $placeholders = [];
                foreach ($user_ids as $idx => $uid) {
                    $key = "uid_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $uid;
                }
                $where .= " AND user_id IN (" . implode(", ", $placeholders) . ")";
            }
            if (!empty($location_ids)) {
                $placeholders = [];
                foreach ($location_ids as $idx => $lid) {
                    $key = "lid_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $lid;
                }
                $where .= " AND location_id IN (" . implode(", ", $placeholders) . ")";
            }
            if (!empty($department_ids)) {
                $placeholders = [];
                foreach ($department_ids as $idx => $did) {
                    $key = "did_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $did;
                }
                $where .= " AND user_id IN (SELECT id FROM company_employees WHERE department_id IN (" . implode(", ", $placeholders) . "))";
            }
            if ($company_id) {
                $where .= " AND company_id = :company_id";
                $params["company_id"] = $company_id;
            }

            $user_in_out_list = DbHelper::findAll(UserInOut::class, $where, $params, "id ASC");

            $employees_cache = [];
            $grouped_by_user = [];

            foreach ($user_in_out_list as $uio) {
                if (!$uio->user) {
                    continue;
                }
                $emp_id = (int)$uio->user;
                if (!isset($employees_cache[$emp_id])) {
                    $employees_cache[$emp_id] = DbHelper::findById(CompanyEmployee::class, $emp_id);
                }
                $user = $employees_cache[$emp_id];
                if (!$user) {
                    continue;
                }
                if (!isset($grouped_by_user[$emp_id])) {
                    $grouped_by_user[$emp_id] = [$user, []];
                }
                $grouped_by_user[$emp_id][1][] = $uio;
            }

            $date_range = [];
            $curr = clone $start_local;
            while ($curr <= $end_local) {
                $date_range[] = clone $curr;
                $curr->modify('+1 day');
            }

            $user_groups = [];
            foreach ($grouped_by_user as $emp_id => $pair) {
                $user = $pair[0];
                $entries = $pair[1];

                $regular_minutes = 0;
                $break_minutes = $user->lunchBreak !== null ? (int)$user->lunchBreak : 0;

                if ($user->companyShift) {
                    $shift = DbHelper::findById(CompanyShift::class, $user->companyShift);
                    if ($shift && $shift->totalHours !== null) {
                        $regular_minutes = (int)round($shift->totalHours * 60);
                    }
                }

                $name_parts = [$user->firstName, $user->middleName, $user->lastName];
                $name_parts = array_filter(array_map('trim', $name_parts));
                $user_name = implode(" ", $name_parts);

                $entries_by_date = [];
                foreach ($entries as $uio) {
                    $created_dt = new DateTime($uio->createdOn, $utc_zone);
                    $created_dt->setTimezone($zone);
                    $created_date = $created_dt->format("Y-m-d");
                    if (!isset($entries_by_date[$created_date])) {
                        $entries_by_date[$created_date] = [];
                    }
                    $entries_by_date[$created_date][] = $uio;
                }

                foreach ($entries_by_date as $date_str => &$day_entries) {
                    usort($day_entries, function($a, $b) {
                        return strcmp($a->createdOn, $b->createdOn);
                    });
                }
                unset($day_entries);

                $holiday_dates = [];
                if ($user->companyDetails) {
                    $holiday_templates = DbHelper::findAll(HolidayTemplates::class, "company_id = :comp_id", ["comp_id" => $user->companyDetails]);
                    foreach ($holiday_templates as $template) {
                        $details = DbHelper::findAll(HolidayTemplateDetails::class, "holiday_template_id = :template_id", ["template_id" => $template->id]);
                        foreach ($details as $detail) {
                            if ($detail->date) {
                                if ($detail->date instanceof DateTime) {
                                    $holiday_dates[] = $detail->date->format("d/m/Y");
                                } else {
                                    $d_dt = new DateTime($detail->date);
                                    $holiday_dates[] = $d_dt->format("d/m/Y");
                                }
                            }
                        }
                    }
                }

                $weekly_off = null;
                if ($user->weeklyOff) {
                    $weekly_off = DbHelper::findById(WeeklyOff::class, $user->weeklyOff);
                }

                $present_count = 0;
                $absent_count = 0;
                $weekly_off_count = 0;
                $holiday_count = 0;

                $data_list = [];
                $total_gross_minutes = 0;
                $total_overtime_minutes = 0;
                $row_index = 1;

                foreach ($date_range as $date_val) {
                    $date_str = $date_val->format("Y-m-d");
                    $day_entries = $entries_by_date[$date_str] ?? null;

                    $is_holiday = false;
                    $is_weekly_off = false;
                    $formatted_current_date = $date_val->format("d/m/Y");

                    if (in_array($formatted_current_date, $holiday_dates)) {
                        $is_holiday = true;
                    }
                    if (!$is_holiday && $weekly_off) {
                        $is_weekly_off = $this->is_weekly_off_day($date_val, $weekly_off);
                    }

                    $has_valid_present_entry = false;
                    if ($day_entries) {
                        foreach ($day_entries as $uio) {
                            if ($uio->timeIn !== null && $uio->timeOut !== null) {
                                $has_valid_present_entry = true;
                                break;
                            }
                        }
                    }

                    if ($is_holiday) {
                        $holiday_count += 1;
                    }
                    if ($is_weekly_off && !$has_valid_present_entry) {
                        $weekly_off_count += 1;
                    }
                    if ($has_valid_present_entry) {
                        $present_count += 1;
                    } else {
                        if (!$is_weekly_off && !$is_holiday) {
                            $absent_count += 1;
                        }
                    }

                    if (!$day_entries) {
                        $created_on_dt = new DateTime($date_val->format("Y-m-d 00:00:00"), $zone);
                        $data_item = [
                            "id" => null,
                            "timeIn" => null,
                            "timeOut" => null,
                            "createdOn" => $this->common_service->convert_date_to_string($created_on_dt, $time_zone),
                            "locationId" => null,
                            "regular" => $this->format_minutes_to_hhmm($regular_minutes),
                            "breakTime" => $this->format_minutes_to_hhmm($break_minutes),
                            "workHours" => "00:00",
                            "overtime" => "00:00",
                            "totalHours" => "00:00"
                        ];
                        if ($is_weekly_off) {
                            $status = "W";
                        } else if ($is_holiday) {
                            $status = "H";
                        } else {
                            $status = "A";
                        }
                        $data_item["status"] = $status;
                        $data_item["userName"] = $user_name;
                        $data_item["rowId"] = $row_index;
                        $row_index += 1;
                        $data_list[] = $data_item;
                    } else {
                        $day_total_gross_minutes = 0;
                        foreach ($day_entries as $uio) {
                            if ($uio->timeIn !== null && $uio->timeOut !== null) {
                                $t_in = new DateTime($uio->timeIn, $utc_zone);
                                $t_out = new DateTime($uio->timeOut, $utc_zone);
                                $diff_sec = $t_out->getTimestamp() - $t_in->getTimestamp();
                                $gross_minutes = (int)($diff_sec / 60);
                                $day_total_gross_minutes += $gross_minutes;
                            }
                        }

                        $day_net_minutes = max(0, $day_total_gross_minutes - $break_minutes);
                        $day_overtime_minutes = max(0, $day_net_minutes - $regular_minutes);

                        $total_gross_minutes += $day_total_gross_minutes;
                        $total_overtime_minutes += $day_overtime_minutes;

                        $is_first = true;
                        foreach ($day_entries as $uio) {
                            $data_item = [];
                            $has_valid_times = ($uio->timeIn !== null && $uio->timeOut !== null);

                            if ($has_valid_times) {
                                $t_in = new DateTime($uio->timeIn, $utc_zone);
                                $t_out = new DateTime($uio->timeOut, $utc_zone);
                                $c_on = new DateTime($uio->createdOn, $utc_zone);

                                $data_item["id"] = $uio->id;
                                $data_item["timeIn"] = $this->common_service->convert_date_to_string($t_in, $time_zone);
                                $data_item["timeOut"] = $this->common_service->convert_date_to_string($t_out, $time_zone);
                                $data_item["createdOn"] = $this->common_service->convert_date_to_string($c_on, $time_zone);
                                $data_item["locationId"] = $uio->locations !== null ? (int)$uio->locations : null;

                                if ($is_first) {
                                    $data_item["regular"] = $this->format_minutes_to_hhmm($regular_minutes);
                                    $data_item["breakTime"] = $this->format_minutes_to_hhmm($break_minutes);
                                    $data_item["workHours"] = $this->format_minutes_to_hhmm($day_net_minutes);
                                    $data_item["overtime"] = $this->format_minutes_to_hhmm($day_overtime_minutes);
                                    $data_item["totalHours"] = $this->format_minutes_to_hhmm($day_total_gross_minutes);

                                    if ($is_holiday || $is_weekly_off) {
                                        $status = "PW";
                                    } else {
                                        $status = "P";
                                    }
                                    $data_item["status"] = $status;
                                    $is_first = false;
                                } else {
                                    $data_item["regular"] = "";
                                    $data_item["breakTime"] = "";
                                    $data_item["workHours"] = "";
                                    $data_item["overtime"] = "";
                                    $data_item["totalHours"] = "";
                                    $data_item["status"] = "";
                                }
                            } else {
                                $t_in = $uio->timeIn ? new DateTime($uio->timeIn, $utc_zone) : null;
                                $t_out = $uio->timeOut ? new DateTime($uio->timeOut, $utc_zone) : null;
                                $c_on = new DateTime($uio->createdOn, $utc_zone);

                                $data_item["id"] = $uio->id;
                                $data_item["timeIn"] = $t_in ? $this->common_service->convert_date_to_string($t_in, $time_zone) : null;
                                $data_item["timeOut"] = $t_out ? $this->common_service->convert_date_to_string($t_out, $time_zone) : null;
                                $data_item["createdOn"] = $this->common_service->convert_date_to_string($c_on, $time_zone);
                                $data_item["locationId"] = $uio->locations !== null ? (int)$uio->locations : null;

                                if ($is_first) {
                                    $data_item["regular"] = $this->format_minutes_to_hhmm($regular_minutes);
                                    $data_item["breakTime"] = $this->format_minutes_to_hhmm($break_minutes);
                                    $data_item["workHours"] = "00:00";
                                    $data_item["overtime"] = "00:00";
                                    $data_item["totalHours"] = "00:00";

                                    if ($is_weekly_off) {
                                        $status = "W";
                                    } else if ($is_holiday) {
                                        $status = "H";
                                    } else {
                                        $status = "A";
                                    }
                                    $data_item["status"] = $status;
                                    $is_first = false;
                                } else {
                                    $data_item["regular"] = "";
                                    $data_item["breakTime"] = "";
                                    $data_item["workHours"] = "";
                                    $data_item["overtime"] = "";
                                    $data_item["totalHours"] = "";
                                    $data_item["status"] = "";
                                }
                            }
                            $data_item["userName"] = $user_name;
                            $data_item["rowId"] = $row_index;
                            $row_index += 1;
                            $data_list[] = $data_item;
                        }
                    }
                }

                $dept_name = "";
                if ($user->department) {
                    $dept = DbHelper::findById(Department::class, $user->department);
                    $dept_name = $dept ? $dept->departmentName : "";
                }

                $user_groups[] = [
                    "id" => $user->employeeId,
                    "username" => $user_name,
                    "presentCount" => $present_count,
                    "absentCount" => $absent_count,
                    "weeklyOffCount" => $weekly_off_count,
                    "holidayCount" => $holiday_count,
                    "department" => $dept_name,
                    "data" => $data_list,
                    "totalHours" => $this->format_minutes_to_hhmm($total_gross_minutes),
                    "totalOvertime" => $this->format_minutes_to_hhmm($total_overtime_minutes)
                ];
            }

            return ["users" => $user_groups];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_entries_by_user_id($user_ids, $start_date_str, $end_date_str, $time_zone, $location_ids, $department_ids, $company_id) {
        try {
            $zone = new DateTimeZone($this->resolve_time_zone($time_zone));
            $utc_zone = new DateTimeZone("UTC");

            if (!$start_date_str || !$end_date_str) {
                $now = new DateTime("now", $utc_zone);
                $start_local = DateTime::createFromFormat("Y-m-d", $now->format("Y-m-01"));
                $end_local = clone $now;
                
                $start_zdt = new DateTime($start_local->format("Y-m-d 00:00:00"), $utc_zone);
                $end_zdt = new DateTime($end_local->format("Y-m-d 23:59:59"), $utc_zone);
            } else {
                $start_local = $this->parse_date_string($start_date_str);
                $end_local = $this->parse_date_string($end_date_str);
                
                $start_zdt = new DateTime($start_local->format("Y-m-d 00:00:00"), $zone);
                $end_zdt = new DateTime($end_local->format("Y-m-d 23:59:59"), $zone);
            }

            $start_utc = clone $start_zdt;
            $start_utc->setTimezone($utc_zone);
            $end_utc = clone $end_zdt;
            $end_utc->setTimezone($utc_zone);

            $where = "created_on >= :start_utc AND created_on <= :end_utc";
            $params = [
                "start_utc" => $start_utc->format('Y-m-d H:i:s'),
                "end_utc" => $end_utc->format('Y-m-d H:i:s')
            ];

            if (!empty($user_ids)) {
                $placeholders = [];
                foreach ($user_ids as $idx => $uid) {
                    $key = "uid_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $uid;
                }
                $where .= " AND user_id IN (" . implode(", ", $placeholders) . ")";
            }
            if (!empty($location_ids)) {
                $placeholders = [];
                foreach ($location_ids as $idx => $lid) {
                    $key = "lid_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $lid;
                }
                $where .= " AND location_id IN (" . implode(", ", $placeholders) . ")";
            }
            if (!empty($department_ids)) {
                $placeholders = [];
                foreach ($department_ids as $idx => $did) {
                    $key = "did_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $did;
                }
                $where .= " AND user_id IN (SELECT id FROM company_employees WHERE department_id IN (" . implode(", ", $placeholders) . "))";
            }
            if ($company_id) {
                $where .= " AND company_id = :company_id";
                $params["company_id"] = $company_id;
            }

            $user_in_out_list = DbHelper::findAll(UserInOut::class, $where, $params, "id ASC");

            $employees_cache = [];
            $employee_data_map = [];

            foreach ($user_in_out_list as $uio) {
                if (!$uio->user) continue;
                $emp_id = (int)$uio->user;
                if (!isset($employees_cache[$emp_id])) {
                    $employees_cache[$emp_id] = DbHelper::findById(CompanyEmployee::class, $emp_id);
                }
                $user = $employees_cache[$emp_id];
                if (!$user) continue;

                if (!isset($employee_data_map[$emp_id])) {
                    $reg_minutes = 0;
                    $break_minutes = $user->lunchBreak !== null ? (int)$user->lunchBreak : 0;
                    if ($user->companyShift) {
                        $shift = DbHelper::findById(CompanyShift::class, $user->companyShift);
                        if ($shift && $shift->totalHours !== null) {
                            $reg_minutes = (int)round($shift->totalHours * 60);
                        }
                    }
                    $employee_data_map[$emp_id] = [
                        "regularMinutes" => $reg_minutes,
                        "breakMinutes" => $break_minutes
                    ];
                }
            }

            $grouped_by_date_and_user = [];
            foreach ($user_in_out_list as $uio) {
                if (!$uio->user) continue;
                $emp_id = (int)$uio->user;
                $user = $employees_cache[$emp_id] ?? null;
                if (!$user) continue;

                $created_dt = new DateTime($uio->createdOn, $utc_zone);
                $created_dt->setTimezone($zone);
                $date_val = $created_dt->format("Y-m-d");

                if (!isset($grouped_by_date_and_user[$date_val])) {
                    $grouped_by_date_and_user[$date_val] = [];
                }
                if (!isset($grouped_by_date_and_user[$date_val][$emp_id])) {
                    $grouped_by_date_and_user[$date_val][$emp_id] = [];
                }
                $grouped_by_date_and_user[$date_val][$emp_id][] = $uio;
            }

            $dto_list = [];
            $sorted_dates = array_keys($grouped_by_date_and_user);
            sort($sorted_dates);

            foreach ($sorted_dates as $date_val) {
                $user_map = $grouped_by_date_and_user[$date_val];
                foreach ($user_map as $emp_id => $day_records) {
                    $emp_data = $employee_data_map[$emp_id] ?? null;
                    $regular_minutes = $emp_data ? $emp_data["regularMinutes"] : 0;
                    $break_minutes = $emp_data ? $emp_data["breakMinutes"] : 0;

                    usort($day_records, function($a, $b) {
                        return strcmp($a->createdOn, $b->createdOn);
                    });

                    $first_record = $day_records[0];
                    $user = $employees_cache[$emp_id];

                    $name_parts = [$user->firstName, $user->middleName, $user->lastName];
                    $name_parts = array_filter(array_map('trim', $name_parts));
                    $user_name = implode(" ", $name_parts);

                    $c_on = new DateTime($first_record->createdOn, $utc_zone);
                    $dto = [
                        "id" => $first_record->id,
                        "userName" => $user_name,
                        "hourlyRate" => $user->hourlyRate !== null ? (float)$user->hourlyRate : null,
                        "firstName" => $user->firstName,
                        "lastName" => $user->lastName,
                        "createdOn" => $this->common_service->convert_date_to_string($c_on, $time_zone),
                        "userId" => $emp_id,
                        "isSalaryGenerate" => $first_record->isSalaryGenerate !== null ? (int)$first_record->isSalaryGenerate : null,
                        "companyId" => $first_record->companyDetails !== null ? (int)$first_record->companyDetails : null,
                        "locationId" => $first_record->locations !== null ? (int)$first_record->locations : null
                    ];

                    $company_shift_dto = null;
                    if ($user->companyShift) {
                        $shift = DbHelper::findById(CompanyShift::class, $user->companyShift);
                        if ($shift) {
                            $s_start = $shift->startTime ? new DateTime($shift->startTime, $utc_zone) : null;
                            $s_end = $shift->endTime ? new DateTime($shift->endTime, $utc_zone) : null;
                            $company_shift_dto = [
                                "id" => $shift->id,
                                "shiftName" => $shift->shiftName,
                                "startTime" => $s_start ? $this->common_service->convert_date_to_string($s_start, $time_zone) : null,
                                "endTime" => $s_end ? $this->common_service->convert_date_to_string($s_end, $time_zone) : null,
                                "totalHours" => $shift->totalHours !== null ? (float)$shift->totalHours : null,
                                "companyId" => $shift->companyDetails !== null ? (int)$shift->companyDetails : null
                            ];
                        }
                    }
                    $dto["companyShiftDto"] = $company_shift_dto;

                    $first_time_in = null;
                    $last_time_out = null;
                    $day_total_gross_minutes = 0;
                    $has_any_valid_times = false;

                    foreach ($day_records as $record) {
                        if ($record->timeIn !== null) {
                            $t_in = new DateTime($record->timeIn, $utc_zone);
                            if ($first_time_in === null || $t_in < $first_time_in) {
                                $first_time_in = $t_in;
                            }
                        }
                        if ($record->timeOut !== null) {
                            $t_out = new DateTime($record->timeOut, $utc_zone);
                            if ($last_time_out === null || $t_out > $last_time_out) {
                                $last_time_out = $t_out;
                            }
                        }
                        if ($record->timeIn !== null && $record->timeOut !== null) {
                            $has_any_valid_times = true;
                            $t_in = new DateTime($record->timeIn, $utc_zone);
                            $t_out = new DateTime($record->timeOut, $utc_zone);
                            $diff_sec = $t_out->getTimestamp() - $t_in->getTimestamp();
                            $gross_minutes = (int)($diff_sec / 60);
                            $day_total_gross_minutes += $gross_minutes;
                        }
                    }

                    $dto["timeIn"] = $first_time_in ? $this->common_service->convert_date_to_string($first_time_in, $time_zone) : null;
                    $dto["timeOut"] = $last_time_out ? $this->common_service->convert_date_to_string($last_time_out, $time_zone) : null;

                    if ($has_any_valid_times) {
                        $work_minutes = max(0, $day_total_gross_minutes - $break_minutes);
                        $overtime_minutes = max(0, $work_minutes - $regular_minutes);

                        $dto["regular"] = $this->format_minutes_to_hhmm($regular_minutes);
                        $dto["breakTime"] = $this->format_minutes_to_hhmm($break_minutes);
                        $dto["workHours"] = $this->format_minutes_to_hhmm($work_minutes);
                        $dto["overtime"] = $this->format_minutes_to_hhmm($overtime_minutes);
                        $dto["totalHours"] = $this->format_minutes_to_hhmm($day_total_gross_minutes);
                    } else {
                        $dto["regular"] = $this->format_minutes_to_hhmm($regular_minutes);
                        $dto["breakTime"] = $this->format_minutes_to_hhmm($break_minutes);
                        $dto["workHours"] = "00:00";
                        $dto["overtime"] = "00:00";
                        $dto["totalHours"] = "00:00";
                    }

                    $dept_name = "";
                    if ($user->department) {
                        $dept = DbHelper::findById(Department::class, $user->department);
                        $dept_name = $dept ? $dept->departmentName : "";
                    }
                    $dto["department"] = $dept_name;
                    $dto["status"] = $first_time_in ? "P" : "A";

                    $dto_list[] = $dto;
                }
            }

            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_user_last_inout($id) {
        try {
            $user_in_out = DbHelper::findFirst(UserInOut::class, "user_id = :user_id", ["user_id" => $id], "id DESC");
            if ($user_in_out) {
                $t_in = $user_in_out->timeIn ? new DateTime($user_in_out->timeIn, new DateTimeZone("UTC")) : null;
                $t_out = $user_in_out->timeOut ? new DateTime($user_in_out->timeOut, new DateTimeZone("UTC")) : null;
                $c_on = $user_in_out->createdOn ? new DateTime($user_in_out->createdOn, new DateTimeZone("UTC")) : null;

                return [
                    "id" => $user_in_out->id,
                    "userId" => $user_in_out->user !== null ? (int)$user_in_out->user : null,
                    "timeIn" => $t_in ? $this->common_service->convert_date_to_string($t_in, "Asia/Calcutta") : null,
                    "timeOut" => $t_out ? $this->common_service->convert_date_to_string($t_out, "Asia/Calcutta") : null,
                    "locationId" => $user_in_out->locations !== null ? (int)$user_in_out->locations : null,
                    "createdOn" => $c_on ? $this->common_service->convert_date_to_string($c_on, "Asia/Calcutta") : null,
                    "companyId" => $user_in_out->companyDetails !== null ? (int)$user_in_out->companyDetails : null,
                    "isSalaryGenerate" => $user_in_out->isSalaryGenerate !== null ? (int)$user_in_out->isSalaryGenerate : null
                ];
            }
            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_user_inout($id) {
        try {
            $user_in_out = DbHelper::findById(UserInOut::class, $id);
            if (!$user_in_out) {
                throw new Exception("UserInOut not found");
            }
            $t_in = $user_in_out->timeIn ? new DateTime($user_in_out->timeIn, new DateTimeZone("UTC")) : null;
            $t_out = $user_in_out->timeOut ? new DateTime($user_in_out->timeOut, new DateTimeZone("UTC")) : null;

            return [
                "id" => $user_in_out->id,
                "userId" => $user_in_out->user !== null ? (int)$user_in_out->user : null,
                "timeIn" => $t_in ? $this->common_service->convert_date_to_string($t_in, "Asia/Calcutta") : null,
                "timeOut" => $t_out ? $this->common_service->convert_date_to_string($t_out, "Asia/Calcutta") : null,
                "locationId" => $user_in_out->locations !== null ? (int)$user_in_out->locations : null
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_user_inout($user_id, $location_id = null, $company_id = null, DateTime $time_in = null) {
        try {
            if ($time_in === null) {
                $time_in = new DateTime("now", new DateTimeZone("UTC"));
            } else {
                $time_in->setTimezone(new DateTimeZone("UTC"));
            }

            $employee = DbHelper::findById(CompanyEmployee::class, $user_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }
            
            $comp_id = $company_id ? $company_id : $employee->companyDetails;

            $user_in_out = new UserInOut();
            $user_in_out->user = $user_id;
            $user_in_out->companyDetails = $comp_id;
            $user_in_out->timeIn = $time_in;
            $user_in_out->createdOn = $time_in;
            $user_in_out->isSalaryGenerate = 0;

            if ($location_id && $location_id > 0) {
                $user_in_out->locations = $location_id;
            }

            $user_in_out = DbHelper::insert($user_in_out);
            return ["id" => $user_in_out->id];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function handle_timeout_update(CompanyEmployee $employee, UserInOut $existing_record, DateTime $time_out, $location_id, $company_id) {
        $auto_time_in_after = null;
        if ($employee->companyDetails) {
            $comp = DbHelper::findById(CompanyDetails::class, $employee->companyDetails);
            $auto_time_in_after = ($comp && isset($comp->autoTimeInAfterHours)) ? $comp->autoTimeInAfterHours : null;
        }

        $time_out->setTimezone(new DateTimeZone("UTC"));

        if (!$auto_time_in_after || trim($auto_time_in_after) === "") {
            $existing_record->timeOut = $time_out;
            DbHelper::update($existing_record);
            return true;
        }

        $ist_zone = new DateTimeZone("Asia/Kolkata");
        
        $t_in_utc = $existing_record->timeIn instanceof DateTime ? $existing_record->timeIn : new DateTime($existing_record->timeIn, new DateTimeZone("UTC"));
        $time_in_ist = clone $t_in_utc;
        $time_in_ist->setTimezone($ist_zone);
        
        $time_out_ist = clone $time_out;
        $time_out_ist->setTimezone($ist_zone);

        $limit_hours = 0;
        $limit_minutes = 0;
        try {
            $parts = explode(":", $auto_time_in_after);
            $limit_hours = (int)$parts[0];
            $limit_minutes = (int)$parts[1];
        } catch (Exception $ex) {
            // Ignore
        }

        $allowed_limit_seconds = ($limit_hours * 3600) + ($limit_minutes * 60);
        $session_duration_seconds = $time_out_ist->getTimestamp() - $time_in_ist->getTimestamp();

        if ($session_duration_seconds > $allowed_limit_seconds) {
            $next_day_time_in = clone $time_out;
            $this->create_user_inout($employee->employeeId, $location_id, $company_id, $next_day_time_in);
            return true;
        } else {
            $existing_record->timeOut = $time_out;
            DbHelper::update($existing_record);
            return true;
        }
    }

    public function update_user_inout_by_id($id, $user_id) {
        try {
            $user_in_out = DbHelper::findById(UserInOut::class, $id);
            if (!$user_in_out) {
                throw new Exception("Record not found");
            }
            $employee = DbHelper::findById(CompanyEmployee::class, $user_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }
            $now = new DateTime("now", new DateTimeZone("UTC"));
            $location_id = $user_in_out->locations;
            $company_id = $employee->companyDetails;
            $this->handle_timeout_update($employee, $user_in_out, $now, $location_id, $company_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_user_inout_by_dto($dto) {
        try {
            $user_in_out = DbHelper::findById(UserInOut::class, $dto["id"]);
            if (!$user_in_out) {
                throw new Exception("Record not found");
            }
            $employee = DbHelper::findById(CompanyEmployee::class, $dto["userId"]);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            if (empty($dto["timeOut"])) {
                throw new Exception("TimeOut is required for update");
            }

            $time_out = $this->common_service->convert_string_to_date($dto["timeOut"]);
            $location_id = $dto["locationId"] ?? null;
            $company_id = $employee->companyDetails;

            $this->handle_timeout_update($employee, $user_in_out, $time_out, $location_id, $company_id);
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function click_in_out($user_id, $location_id = null, $company_id = null) {
        try {
            $employee = DbHelper::findById(CompanyEmployee::class, $user_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $existing = DbHelper::findFirst(UserInOut::class, "user_id = :user_id AND time_out IS NULL", ["user_id" => $user_id], "id DESC");

            if ($existing) {
                $this->update_user_inout_by_id($existing->id, $user_id);
                $username = $employee->userName ? $employee->userName : ($employee->firstName . " " . $employee->lastName);
                return "updated:" . $username;
            } else {
                $now = new DateTime("now", new DateTimeZone("UTC"));
                $this->create_user_inout($user_id, $location_id, $company_id, $now);
                $username = $employee->userName ? $employee->userName : ($employee->firstName . " " . $employee->lastName);
                return "created:" . $username;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function parse_any_date($s) {
        if (!$s || !trim($s)) {
            return null;
        }
        try {
            return $this->common_service->convert_string_to_date($s);
        } catch (Exception $e) {
            throw new Exception("Invalid date format: " . $s);
        }
    }

    public function get_today_entries_by_user_id($user_id) {
        try {
            $tz = new DateTimeZone("Asia/Kolkata");
            $now = new DateTime("now", $tz);
            
            $start_of_day = (clone $now)->setTime(0, 0, 0)->setTimezone(new DateTimeZone("UTC"))->format("Y-m-d H:i:s");
            $end_of_day = (clone $now)->setTime(23, 59, 59)->setTimezone(new DateTimeZone("UTC"))->format("Y-m-d H:i:s");

            $entries = DbHelper::findAll(UserInOut::class, "user_id = :user_id AND created_on >= :start AND created_on <= :end", [
                "user_id" => $user_id,
                "start" => $start_of_day,
                "end" => $end_of_day
            ]);

            $dto_list = [];
            foreach ($entries as $entry) {
                $t_in = $entry->timeIn ? new DateTime($entry->timeIn, new DateTimeZone("UTC")) : null;
                $t_out = $entry->timeOut ? new DateTime($entry->timeOut, new DateTimeZone("UTC")) : null;
                
                $dto_list[] = [
                    "id" => $entry->id,
                    "timeIn" => $t_in ? $this->common_service->convert_date_to_string($t_in, "Asia/Calcutta") : null,
                    "timeOut" => $t_out ? $this->common_service->convert_date_to_string($t_out, "Asia/Calcutta") : null,
                    "userId" => $entry->user !== null ? (int)$entry->user : null
                ];
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_time_inout_report($user_ids, $start_date_str, $end_date_str, $time_zone, $company_id) {
        try {
            $utc_zone = new DateTimeZone("UTC");
            if (!$start_date_str || !$end_date_str) {
                $now = new DateTime("now", $utc_zone);
                $start = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
                $end = (clone $now)->modify('last day of this month')->setTime(23, 59, 59);
            } else {
                $start = $this->common_service->convert_local_to_utc($start_date_str, $time_zone, false);
                $end = $this->common_service->convert_local_to_utc($end_date_str, $time_zone, true);
            }

            if (!empty($user_ids)) {
                $placeholders = [];
                $params = [];
                foreach ($user_ids as $idx => $uid) {
                    $key = "uid_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $uid;
                }
                $users = DbHelper::findAll(CompanyEmployee::class, "id IN (" . implode(", ", $placeholders) . ")", $params);
            } else {
                if ($company_id) {
                    $users = DbHelper::findAll(CompanyEmployee::class, "company_id = :comp_id", ["comp_id" => $company_id]);
                } else {
                    $users = DbHelper::findAll(CompanyEmployee::class);
                }
            }

            $user_map = [];
            foreach ($users as $u) {
                $user_map[$u->employeeId] = $u->firstName . " " . $u->lastName;
            }

            $where = "created_on >= :start AND created_on <= :end";
            $params = [
                "start" => $start->format('Y-m-d H:i:s'),
                "end" => $end->format('Y-m-d H:i:s')
            ];
            if (!empty($user_ids)) {
                $placeholders = [];
                foreach ($user_ids as $idx => $uid) {
                    $key = "uid_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $uid;
                }
                $where .= " AND user_id IN (" . implode(", ", $placeholders) . ")";
            }
            if ($company_id) {
                $where .= " AND company_id = :company_id";
                $params["company_id"] = $company_id;
            }

            $user_in_out_records = DbHelper::findAll(UserInOut::class, $where, $params);
            $user_in_out_map = [];
            foreach ($user_in_out_records as $record) {
                if (!$record->user) continue;
                $emp_id = (int)$record->user;
                if (!isset($user_in_out_map[$emp_id])) {
                    $user_in_out_map[$emp_id] = [];
                }
                $user_in_out_map[$emp_id][] = $record;
            }

            $response_list = [];
            foreach ($user_map as $current_user_id => $user_name) {
                $records = $user_in_out_map[$current_user_id] ?? [];
                $monthly_records = [];

                foreach ($records as $record) {
                    $c_on = new DateTime($record->createdOn, $utc_zone);
                    $month = (int)$c_on->format('n');

                    $t_in = $record->timeIn ? new DateTime($record->timeIn, $utc_zone) : null;
                    $t_out = $record->timeOut ? new DateTime($record->timeOut, $utc_zone) : null;

                    $time_in_str = $t_in ? $this->common_service->convert_date_to_string($t_in, $time_zone) : "";
                    $time_out_str = $t_out ? $this->common_service->convert_date_to_string($t_out, $time_zone) : "";

                    $day_record = [
                        "records" => [
                            [
                                "timeIn" => $time_in_str,
                                "timeOut" => $time_out_str
                            ]
                        ]
                    ];
                    if (!isset($monthly_records[$month])) {
                        $monthly_records[$month] = [];
                    }
                    $monthly_records[$month][] = $day_record;
                }

                $month_data = [];
                foreach ($monthly_records as $m => $data_list) {
                    $month_data[] = [
                        "month" => $m,
                        "data" => $data_list
                    ];
                }
                $response_list[] = [
                    "username" => $user_name,
                    "records" => $month_data
                ];
            }

            return ["data" => $response_list];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function generate_excel_report($data, $start_date_str, $end_date_str, $time_zone) {
        $month_format = "F Y";
        
        $start_date = null;
        $end_date = null;

        try {
            if ($start_date_str) {
                $parts = explode(",", $start_date_str);
                $start_date = DateTime::createFromFormat("m/d/Y", trim($parts[0]));
            }
            if ($end_date_str) {
                $parts = explode(",", $end_date_str);
                $end_date = DateTime::createFromFormat("m/d/Y", trim($parts[0]));
            }
        } catch (Exception $e) {
            // Ignore
        }

        if (!$start_date || !$end_date) {
            $now = new DateTime("now", new DateTimeZone("UTC"));
            $start_date = (clone $now)->modify('first day of this month');
            $end_date = (clone $now)->modify('last day of this month');
        }

        $month_keys = [];
        $curr = clone $start_date;
        while ($curr <= $end_date) {
            $m_key = $curr->format("F Y");
            if (!in_array($m_key, $month_keys)) {
                $month_keys[] = $m_key;
            }
            $curr->modify('+1 month');
        }

        $xml = '<?xml version="1.0"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        $xml .= ' <Styles>' . "\n";
        $xml .= '  <Style ss:ID="Default" ss:Name="Normal">' . "\n";
        $xml .= '   <Alignment ss:Vertical="Bottom"/>' . "\n";
        $xml .= '   <Borders/>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>' . "\n";
        $xml .= '   <Interior/>' . "\n";
        $xml .= '   <NumberFormat/>' . "\n";
        $xml .= '   <Protection/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="Title">' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" ss:Size="14" ss:Color="#000000" ss:Bold="1"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="Month">' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" ss:Size="14" ss:Color="#000000" ss:Bold="1"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="Header">' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" ss:Size="12" ss:Color="#000000" ss:Bold="1"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="UserCell">' . "\n";
        $xml .= '   <Alignment ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="DataCell">' . "\n";
        $xml .= '   <Alignment ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="TotalCell">' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= ' </Styles>' . "\n";

        $sheets_meta = [];
        foreach ($month_keys as $m_key) {
            $sheet_month_date = DateTime::createFromFormat("F Y", $m_key);
            if (!$sheet_month_date) {
                $sheet_month_date = clone $start_date;
            }
            
            $sheet_start_date = (clone $sheet_month_date)->modify('first day of this month');
            $sheet_end_date = (clone $sheet_month_date)->modify('last day of this month');
            
            $start_day = 1;
            $end_day = (int)$sheet_end_date->format('d');
            
            if ($start_date->format('m-Y') === $sheet_start_date->format('m-Y')) {
                $start_day = (int)$start_date->format('d');
            }
            if ($end_date->format('m-Y') === $sheet_end_date->format('m-Y')) {
                $end_day = (int)$end_date->format('d');
            }
            
            $total_columns = $end_day - $start_day + 2;
            $sheets_meta[$m_key] = [
                "start_day" => $start_day,
                "end_day" => $end_day,
                "total_columns" => $total_columns
            ];
        }

        $user_data_list = $data["data"] ?? [];

        foreach ($month_keys as $m_key) {
            $meta = $sheets_meta[$m_key];
            $start_day = $meta["start_day"];
            $end_day = $meta["end_day"];
            $total_columns = $meta["total_columns"];
            
            $sheet_title = str_replace("/", "-", $m_key);
            $xml .= ' <Worksheet ss:Name="' . htmlspecialchars($sheet_title) . '">' . "\n";
            $xml .= '  <Table>' . "\n";
            
            $xml .= '   <Row ss:Height="25">' . "\n";
            $xml .= '    <Cell ss:MergeAcross="' . ($total_columns) . '" ss:StyleID="Title"><Data ss:Type="String">In-Out Report</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
            
            $xml .= '   <Row ss:Height="25">' . "\n";
            $xml .= '    <Cell ss:MergeAcross="' . ($total_columns) . '" ss:StyleID="Month"><Data ss:Type="String">' . htmlspecialchars($m_key) . '</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
            
            $xml .= '   <Row ss:Height="20">' . "\n";
            $xml .= '    <Cell ss:StyleID="Header"><Data ss:Type="String">User Name</Data></Cell>' . "\n";
            for ($d = $start_day; $d <= $end_day; $d++) {
                $xml .= '    <Cell ss:StyleID="Header"><Data ss:Type="Number">' . $d . '</Data></Cell>' . "\n";
            }
            $xml .= '    <Cell ss:StyleID="Header"><Data ss:Type="String">Total Hours</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";

            foreach ($user_data_list as $user) {
                $user_name = $user["username"] ?? "";
                $records = $user["records"] ?? [];
                $filtered_records = [];
                
                $sheet_month_date = DateTime::createFromFormat("F Y", $m_key);
                $current_month = $sheet_month_date ? (int)$sheet_month_date->format('n') : (int)$start_date->format('n');
                
                foreach ($records as $record_group) {
                    $record_month = (int)($record_group["month"] ?? 0);
                    if ($record_month === $current_month) {
                        $data_list = $record_group["data"] ?? [];
                        foreach ($data_list as $data_entry) {
                            $time_records = $data_entry["records"] ?? [];
                            foreach ($time_records as $tr) {
                                $filtered_records[] = $tr;
                            }
                        }
                    }
                }

                $xml .= $this->write_user_record_xml($user_name, $filtered_records, $time_zone, $start_day, $end_day);
            }
            
            $xml .= '  </Table>' . "\n";
            $xml .= ' </Worksheet>' . "\n";
        }
        
        $xml .= '</Workbook>' . "\n";
        return $xml;
    }

    private function write_user_record_xml($user_name, $records, $time_zone, $start_day, $end_day) {
        $input_format = "d/m/Y, h:i:s A";
        
        $total_minutes = 0;
        $day_entries = [];

        foreach ($records as $record) {
            $time_in_str = $record["timeIn"] ?? null;
            $time_out_str = $record["timeOut"] ?? null;
            if (!$time_in_str || !$time_out_str) {
                continue;
            }
            try {
                $time_in_local_str = $this->common_service->convert_utc_to_local($time_in_str, $time_zone);
                $time_out_local_str = $this->common_service->convert_utc_to_local($time_out_str, $time_zone);
                
                if (!$time_in_local_str || !$time_out_local_str) {
                    continue;
                }

                $time_in = DateTime::createFromFormat($input_format, $time_in_local_str);
                $time_out = DateTime::createFromFormat($input_format, $time_out_local_str);

                if (!$time_in || !$time_out) {
                    continue;
                }

                $day_of_month = (int)$time_in->format('j');
                $duration_minutes = (int)round(($time_out->getTimestamp() - $time_in->getTimestamp()) / 60.0);
                
                if ($duration_minutes < 0) {
                    $duration_minutes += 24 * 60;
                }

                $total_minutes += $duration_minutes;

                if ($day_of_month >= $start_day && $day_of_month <= $end_day) {
                    if (!isset($day_entries[$day_of_month])) {
                        $day_entries[$day_of_month] = [];
                    }
                    $day_entries[$day_of_month][] = $time_in->format("H:i") . " - " . $time_out->format("H:i");
                }
            } catch (Exception $e) {
                // Ignore
            }
        }

        $xml_row = '   <Row ss:Height="30">' . "\n";
        $xml_row .= '    <Cell ss:StyleID="UserCell"><Data ss:Type="String">' . htmlspecialchars($user_name) . '</Data></Cell>' . "\n";
        
        for ($day = $start_day; $day <= $end_day; $day++) {
            if (isset($day_entries[$day])) {
                $val = implode("\n", $day_entries[$day]);
                $xml_row .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($val) . '</Data></Cell>' . "\n";
            } else {
                $xml_row .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">-</Data></Cell>' . "\n";
            }
        }

        $total_str = $this->format_total_time($total_minutes);
        $xml_row .= '    <Cell ss:StyleID="TotalCell"><Data ss:Type="String">' . htmlspecialchars($total_str) . '</Data></Cell>' . "\n";
        $xml_row .= '   </Row>' . "\n";

        return $xml_row;
    }

    public function delete_user_inout($id) {
        try {
            $user_in_out = DbHelper::findById(UserInOut::class, $id);
            if ($user_in_out) {
                DbHelper::delete(UserInOut::class, $id);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function add_bulk_clock_in_out($bulk_dto) {
        try {
            $user_ids = $bulk_dto["userId"] ?? null;
            if (!$user_ids || !is_array($user_ids)) {
                throw new Exception("User ID list cannot be empty");
            }
            if (empty($bulk_dto["startDate"]) || empty($bulk_dto["endDate"])) {
                throw new Exception("Start date and End date are required");
            }

            $start_dt = $this->parse_any_date($bulk_dto["startDate"]);
            $end_dt = $this->parse_any_date($bulk_dto["endDate"]);

            if (!$start_dt || !$end_dt) {
                throw new Exception("Start date and End date are invalid");
            }

            $start_local = clone $start_dt;
            $start_local->setTimezone(new DateTimeZone("UTC"));
            $end_local = clone $end_dt;
            $end_local->setTimezone(new DateTimeZone("UTC"));

            $start_date_only = DateTime::createFromFormat("Y-m-d", $start_local->format("Y-m-d"));
            $end_date_only = DateTime::createFromFormat("Y-m-d", $end_local->format("Y-m-d"));

            if ($start_date_only > $end_date_only) {
                throw new Exception("Start date cannot be after End date");
            }

            $time_in_dt = $this->parse_any_date($bulk_dto["timeIn"]);
            $time_out_dt = !empty($bulk_dto["timeOut"]) ? $this->parse_any_date($bulk_dto["timeOut"]) : null;

            if (!$time_in_dt) {
                throw new Exception("Time In is required");
            }

            $base_time_in = clone $time_in_dt;
            $base_time_in->setTimezone(new DateTimeZone("UTC"));
            $base_time_out = $time_out_dt ? clone $time_out_dt : null;
            if ($base_time_out) {
                $base_time_out->setTimezone(new DateTimeZone("UTC"));
            }

            $duration_seconds = null;
            if ($base_time_out) {
                $duration_seconds = $base_time_out->getTimestamp() - $base_time_in->getTimestamp();
            }

            $company_details = DbHelper::findById(CompanyDetails::class, $bulk_dto["companyId"]);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            foreach ($user_ids as $user_id) {
                $company_employee = DbHelper::findById(CompanyEmployee::class, $user_id);
                if (!$company_employee) {
                    throw new Exception("Employee not found for id: " . $user_id);
                }

                $weekly_off = null;
                if ($company_employee->weeklyOff) {
                    $weekly_off = DbHelper::findById(WeeklyOff::class, $company_employee->weeklyOff);
                }

                $holiday_dates = [];
                if ($company_employee->holidayTemplates) {
                    $details = DbHelper::findAll(HolidayTemplateDetails::class, "holiday_template_id = :template_id", ["template_id" => $company_employee->holidayTemplates]);
                    foreach ($details as $detail) {
                        if ($detail->date) {
                            if ($detail->date instanceof DateTime) {
                                $holiday_dates[] = $detail->date->format("d/m/Y");
                            } else {
                                $d_dt = new DateTime($detail->date);
                                $holiday_dates[] = $d_dt->format("d/m/Y");
                            }
                        }
                    }
                }

                $curr_date = clone $start_date_only;
                while ($curr_date <= $end_date_only) {
                    $formatted_date = $curr_date->format("d/m/Y");
                    if (!empty($holiday_dates) && in_array($formatted_date, $holiday_dates)) {
                        $curr_date->modify('+1 day');
                        continue;
                    }

                    if ($weekly_off) {
                        if ($this->is_weekly_off_day($curr_date, $weekly_off)) {
                            $curr_date->modify('+1 day');
                            continue;
                        }
                    }

                    $day_start = $curr_date->format("Y-m-d 00:00:00");
                    $day_end = $curr_date->format("Y-m-d 23:59:59");

                    $existing_records = DbHelper::findAll(UserInOut::class, "user_id = :user_id AND created_on >= :day_start AND created_on <= :day_end", [
                        "user_id" => $company_employee->employeeId,
                        "day_start" => $day_start,
                        "day_end" => $day_end
                    ]);

                    if (empty($existing_records)) {
                        $user_in_out = new UserInOut();
                        $user_in_out->user = $company_employee->employeeId;
                        $user_in_out->companyDetails = $company_details->id;
                        $user_in_out->isSalaryGenerate = 0;

                        $new_time_in = clone $base_time_in;
                        $new_time_in->setDate((int)$curr_date->format('Y'), (int)$curr_date->format('m'), (int)$curr_date->format('d'));

                        $new_time_out = null;
                        if ($duration_seconds !== null) {
                            $new_time_out = clone $new_time_in;
                            $new_time_out->modify("+" . $duration_seconds . " seconds");
                        }

                        $user_in_out->timeIn = $new_time_in;
                        $user_in_out->timeOut = $new_time_out;
                        $user_in_out->createdOn = $new_time_in;
                        
                        DbHelper::insert($user_in_out);
                    }

                    $curr_date->modify('+1 day');
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function add_clock_in_out($dto) {
        try {
            if (isset($dto["id"]) && $dto["id"] !== null) {
                $user_in_out = DbHelper::findById(UserInOut::class, $dto["id"]);
                if (!$user_in_out) {
                    throw new Exception("Record not found");
                }
                $employee = DbHelper::findById(CompanyEmployee::class, $dto["userId"]);
                if (!$employee) {
                    throw new Exception("Employee not found");
                }

                if (isset($dto["timeOut"]) && $dto["timeOut"] !== null) {
                    $time_out = $this->parse_any_date($dto["timeOut"]);
                    $location_id = $dto["locationId"] ?? $dto["locations"] ?? null;
                    $company_id = $dto["companyId"] ?? $employee->companyDetails ?? null;
                    $this->handle_timeout_update($employee, $user_in_out, $time_out, $location_id, $company_id);
                }

                if (isset($dto["timeIn"]) && $dto["timeIn"] !== null) {
                    $user_in_out->timeIn = $this->parse_any_date($dto["timeIn"]);
                }

                DbHelper::update($user_in_out);
                return $dto;
            } else {
                $user_id = $dto["userId"] ?? null;
                $company_id = $dto["companyId"] ?? null;
                $time_in_str = $dto["timeIn"] ?? null;
                $time_out_str = $dto["timeOut"] ?? null;
                $created_on_str = $dto["createdOn"] ?? $dto["date"] ?? null;
                $location_id = $dto["locationId"] ?? $dto["locations"] ?? null;

                $employee = DbHelper::findById(CompanyEmployee::class, $user_id);
                if (!$employee) {
                    throw new Exception("Employee not found");
                }

                $company_id = $company_id ? $company_id : $employee->companyDetails;
                $company = DbHelper::findById(CompanyDetails::class, $company_id);
                if (!$company) {
                    throw new Exception("Company not found");
                }

                $time_in = $time_in_str ? $this->parse_any_date($time_in_str) : null;
                if (!$time_in) {
                    throw new Exception("Time In is required");
                }
                $time_out = $time_out_str ? $this->parse_any_date($time_out_str) : null;
                $created_on = $created_on_str ? $this->parse_any_date($created_on_str) : $time_in;

                $user_in_out = new UserInOut();
                $user_in_out->user = $user_id;
                $user_in_out->companyDetails = $company_id;
                $user_in_out->timeIn = $time_in;
                $user_in_out->timeOut = $time_out;
                $user_in_out->createdOn = $created_on;
                $user_in_out->isSalaryGenerate = 0;
                if ($location_id) {
                    $user_in_out->locations = $location_id;
                }

                $user_in_out = DbHelper::insert($user_in_out);
                return [
                    "id" => $user_in_out->id,
                    "timeIn" => $time_in_str,
                    "timeOut" => $time_out_str,
                    "userId" => $user_id,
                    "companyId" => $company_id
                ];
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
