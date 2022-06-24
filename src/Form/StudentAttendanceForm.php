<?php

namespace Drupal\student_attendance\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

class StudentAttendanceForm extends FormBase{

  public function getFormId(){
      return 'student_attendance_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state){
    $form['#attached']['library'][] =  'student_attendance/student_attendance.library';
    $form['student_attendance_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Student Attendance'),
    ];
    $classes_option_list = static::getClassesOptions();
    if(empty($form_state->getValue('classes_list'))){
		$selected_class = key($classes_option_list);
	}else{
	    $selected_class = $form_state->getValue('classes_list');
	}
    
    $form['student_attendance_fieldset']['classes_list'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Class'),
        '#options' => $classes_option_list,
        '#default_value' => $selected_class,
        '#ajax' => [
           'callback' => [$this, 'getSectionCallback'], 
           'wrapper' => 'student-attendance-container',
        ],
    ];

    $form['student_attendance_container'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'student-attendance-container'],
    ];
    $form['student_attendance_container']['sections_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Choose a Section'),
    ];
    $form['student_attendance_container']['sections_fieldset']['choose_sections'] = [
       '#type' => 'select',
       '#title' => /*$classes_option_list[$selected_class] .' '.*/$this->t('Choose Section'),
       '#options' => static::getSections($selected_class),
       '#default_value' => !empty($form_state->getValue('choose_sections')) ? $form_state->getValue('choose_sections'):'',
    ];
    
    //table
    $header = [
      'serialnumber' => $this->t('SNO.'),
      'name' => $this->t('Name'),
      'attendance' => $this->t('Attendance'),
    ];

    $form['attandance'] = array(
      '#type' => 'table',
      '#title' => $this->t('Attendance Table'),
      '#header' => $header,
    );

    // Add input fields in table cells.
   for ($i=1; $i<=10; $i++) {
    $form['attandance'][$i]['#attributes'] = array(
        'class' => array('table','table-bordered'),
      );
    $form['attandance'][$i]['serialnumber'] = [
      '#type' => 'textfield',
      '#default_value' => $i,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['attandance'][$i]['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#title_display' => 'invisible',
    ];


    $form['attandance'][$i]['attend'] = array(
    '#type' => 'radios',
    '#options' => ['yes' => 'Yes', 'no' => 'No'],
    );                                            
   }
    //end table

    $form['submit'] = [
       '#type' => 'submit',
       '#value' => $this->t('SUBMIT'),
       '#attributes' => ['class' => ['btn', 'btn-lg', 'btn-primary', 'm-0', 'mt-4']],
    ];

    return $form;

  }

  public function validateForm(array &$form, FormStateInterface $form_state){

  }
  
  public function submitForm(array &$form, FormStateInterface $form_state){

    $values = $form_state->getValues();
    $attendance_row = [];
    foreach($values['attandance'] as $key=>$value){
        if(!empty(trim($value['name']))){
           $attendance_row[$key]['serialnumber'] = $value['serialnumber'];
           $attendance_row[$key]['name'] = $value['name'];
           $attendance_row[$key]['attend'] = !empty($value['attend']) ? $value['attend'] : 'no';
        }  
    }
    if(!empty(trim($values['classes_list'])) || !empty(trim($values['choose_sections']))){
        $date = date("Y-m-d");
        $selcted_class = $values['classes_list'];
        $selcted_section = $values['choose_sections'];
        $file_system = \Drupal::service('file_system');
        $directory = 'public://student/attendance/'.$date.'/'.$selcted_class.'/'.$selcted_section.'/';
        $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        $file_params = array('filename' => $selcted_section.'.txt', 'uri' => $directory.$selcted_section.'.txt', 'filemime' => 'text/plain', 'status' => FILE_STATUS_PERMANENT);
        // Create a new file entity.
        $file = File::create($file_params);
        $classes_attendance = [$selcted_class => $selcted_section, 'attendance_row' => $attendance_row ,'time'=>date("l jS \of F Y h:i:s A")];
        file_put_contents($file->getFileUri(), json_encode($classes_attendance));
        $file->save();
        $this->messenger()->addMessage($this->t('Attendance save successfully !!'));
    }else{
        $this->messenger()->addMessage($this->t('Please select the class or section !!')); 
    }
  }

  public static function getClassesOptions(){
        return [
          '' => 'Class List', '1' => '1','2' => '2','3' => '3', '4' => '4','5' => '5','6' => '6', '7' => '7','8' => '8','9' => '9' ,'10' => '10','11' => '11','12' => '12'
        ];
  }

  public function getSectionCallback(array $form, FormStateInterface $form_state){
      return $form['student_attendance_container'];
  }

  public function getSections($key = ''){
    switch($key){
        case '1':
          $options = [ "A" => "A", "B" => "B" ];
          break;

        case '2':
          $options = [ "A" => "A", "B" => "B" ];
          break;

        case '3':
          $options = [ "A" => "A", "B" => "B" ];
          break;

        case '4':
          $options = [ "A" => "A", "B" => "B", "C" => "C" ];
          break;

        case '5':
          $options = [ "A" => "A", "B" => "B" ];
          break;

        case '6':
          $options = [ "CP" => "CP", "P" => "P" ];
          break;

        case '7':
          $options = [ "CS-1" => "CS-1", "CS-2" => "CS-2", "P" => "P" ];
          break;

        case '8':
          $options = [ "CS" => "CS", "P" => "P" ];
          break;

        case '9':
          $options = [ "DP" => "DP", "CS" => "CS", "P" => "P", "O" => "O" ];
          break;

        case '10':
          $options = [ "DP" => "DP", "CS" => "CS", "O" => "O" ];
          break;

        case '11':
          $options = [  "Mathematics" => "Mathematics", "Biology" => "Biology", "Commerce" => "Commerce", "Arts" => "Arts" ];
          break;

        case '12':
          $options = [ "Mathematics" => "Mathematics", "Biology" => "Biology", "Commerce" => "Commerce", "Arts" => "Arts", 'CS' => 'CS' ];
          break;

        default:
          $options = [ " " => "Section List"];
          break;
    }
      
     return $options;
  }

}