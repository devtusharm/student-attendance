<?php

namespace Drupal\student_attendance\Controller;
use Drupal\Core\Controller\ControllerBase;

class StudentAttendanceController extends ControllerBase{
  public function list(){
      $item = [
       '#markup' => $this->t('Student Attendance List'),
      ];
      return $item;
  }

} 