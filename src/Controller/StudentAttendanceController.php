<?php

namespace Drupal\student_attendance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for Student Attendance .
 */
class StudentAttendanceController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function list($adate, $aclass, $asection) {

    // \Drupal::service('page_cache_kill_switch')->trigger();
    if (!empty($adate) || !empty($aclass) || !empty($asection)) {
      // Check file exist.
      $file_path = DRUPAL_ROOT . "\sites\default\\files\student\attendance\\$adate\\$aclass\\$asection\\$asection.txt";
      if (file_exists($file_path)) {

        $host = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
        $file_url = "$host/sites/default/files/student/attendance/$adate/$aclass/$asection/$asection.txt";
        $contents = file_get_contents($file_url, TRUE);
        $datas = json_decode($contents);
        $datas_arr = (array) $datas;

        // table1 data.
        $details_header = [
          'date' => $this->t('Attendance Date'),
          'sclass' => $this->t('Class'),
          'ssection' => $this->t('Section'),
          'enteredby' => $this->t('Enter BY'),
        ];
        $rows_header[] = [
          $datas_arr['time'], $aclass, $datas_arr['section'], $datas_arr['username'],
        ];
        $item['attandance_header'] = [
          '#type' => 'table',
          '#header' => $details_header,
          '#rows' => $rows_header,
          '#attributes' => [
            'class' => ['table table-bordered'],
          ],
        ];

        // table2 data.
        $header = [
          'serialnumber' => $this->t('Sno.'),
          'name' => $this->t('Name'),
          'attendance' => $this->t('Attendance'),
        ];

        $rows = [];
        foreach ($datas_arr['attendance_row'] as $data) {
          $rows[] = [$data->serialnumber, $data->name, $data->attend];
        }

        $item['attandance'] = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#attributes' => [
            'class' => ['table table-bordered'],
          ],
        ];
        // End table.
        return $item;
      }
      else {
        return ['#markup' => $this->t('File Not Exist !!!')];
      }

    }

  }

}
