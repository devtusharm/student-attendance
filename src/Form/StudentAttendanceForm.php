<?php

namespace Drupal\student_attendance\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Url;

class StudentAttendanceForm extends FormBase{
  protected $currentUser;
  public static function create(ContainerInterface $container){
      $form = new static();
      $form->currentUser = $container->get('current_user');
      return $form;
  }
  public function getFormId(){
      return 'student_attendance_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state){
    
    //Attach Library
    $form['#attached']['library'][] =  'student_attendance/student_attendance.library';

    //Attendance Form
    $form['student_attendance_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Student Attendance'),
    ];

    //Fetch classes List
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
        //'#default_value' => $selected_class,
        '#ajax' => [
           'callback' => [$this, 'getSectionCallback'],
           'event' => 'change',
           'wrapper' => 'student-section-container',
        ],
    ];

    $form['student_section_container'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'student-section-container'],
    ];

    $form['student_section_container']['sections_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Choose a Section'),
    ];

    $form['student_section_container']['sections_fieldset']['choose_sections'] = [
       '#type' => 'select',
       '#title' => $this->t('Choose Section'),
       '#options' => static::getSections($selected_class),
       '#ajax' => [
          'callback' => [$this, 'getAttendanceSheetCallback'],
          'event' => 'change',
          'wrapper' => 'student-attendance-container',
       ],
    ];

    $form['student_attendance_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'student-attendance-container'],
    ];

    if (!empty($form_state->getValue('choose_sections'))) {

      $sec = $form_state->getValue('choose_sections');
      $host = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

      $today_date = date("Y-m-d");
      $today_file_path = DRUPAL_ROOT. "\sites\default\\files\student\attendance\\$today_date\\$selected_class\\$sec\\$sec.txt";

      if (file_exists($today_file_path)) {
        $attended_url = $host."students/attendance/$today_date/$selected_class/$sec";
        $form['student_attendance_container']['exist_file_page'] = [
           '#markup' => $this->t('Attendance Already Submitted. Please Visit <a href="@URL" target="_blank">@URL</a>',['@URL' => $attended_url]),
        ];
      }
      else {
        $yesterday_date = date("Y-m-d", strtotime("-1 days"));
        // Fetch Previous Day Attendance record for auto load name sync
        $file_path = DRUPAL_ROOT.   "\sites\default\\files\student\attendance\\$yesterday_date\\$selected_class\\$sec\\$sec.txt";
        $datas_arr = []; 
        if (file_exists($file_path)) {
          $file_url = $host."sites/default/files/student/attendance/$yesterday_date/$selected_class/$sec/$sec.txt";
          $contents = file_get_contents($file_url, true);
          $datas = json_decode($contents);
          $datas_arr = (array) $datas;
        }

        //Attendance Table 
        $header = [
          'serialnumber' => $this->t('Sno.'),
          'name' => $this->t('Name'),
          'attendance' => $this->t('Attendance'),
        ];
       
        $form['student_attendance_container']['attandance'] = [
          '#type' => 'table',
          '#header' => $header,
          '#attributes' => [
            'class' => ['table table-bordered'],
          ],
        ];
      
        // Add input fields in table cells.
      
        for ($i=1; $i<=10; $i++) {

          $form['student_attendance_container']['attandance'][$i]['serialnumber'] = [
           '#type' => 'number',
           '#value' => $i,
           '#attributes' => ['readonly' => 'readonly'],
           '#maxlength' => '2',
          ];

          $form['student_attendance_container']['attandance'][$i]['name'] = [
           '#type' => 'textfield',
          '#title' => $this->t('Name'),
           '#title_display' => 'invisible',
           '#value' => !empty( $datas_arr['attendance_row']->$i->name ) ? $datas_arr['attendance_row']->$i->name : '',
          ];

          $form['student_attendance_container']['attandance'][$i]['attend'] = array(
           '#type' => 'radios',
           '#options' => ['yes' => 'Yes', 'no' => 'No'],
           );
        }
      
        //end table
        $form['student_attendance_container']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('SUBMIT'),
          '#attributes' => ['class' => ['btn', 'btn-lg', 'btn-primary', 'm-0', 'mt-4']],
        ];
      }
    }
    return $form;

  }

  public function validateForm(array &$form, FormStateInterface $form_state){

  }
  
  public function submitForm(array &$form, FormStateInterface $form_state){
   
    //Get Login user information
    $currentUsername = $this->currentUser->getAccountName();
    $currentUserid = $this->currentUser->Id();

    $values = $form_state->getValues();
    $attendance_row = [];
    foreach($values['attandance'] as $key=>$value) {
        if(!empty(trim($value['name']))){
           $attendance_row[$key]['serialnumber'] = $value['serialnumber'];
           $attendance_row[$key]['name'] = $value['name'];
           $attendance_row[$key]['attend'] = !empty($value['attend']) ? $value['attend'] : 'no';
        }  
    }
    
    if(!empty(trim($values['classes_list'])) || !empty(trim($values['choose_sections']))) {

        $date = date("Y-m-d");
        $selcted_class = $values['classes_list'];
        $selcted_section = $values['choose_sections'];
        $file_system = \Drupal::service('file_system');
        $directory = 'public://student/attendance/'.$date.'/'.$selcted_class.'/'.$selcted_section.'/';
        $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        $file_params = array('filename' => $selcted_section.'.txt', 'uri' => $directory.$selcted_section.'.txt', 'filemime' => 'text/plain', 'status' => FILE_STATUS_PERMANENT);
        // Create a new file entity.
        $file = File::create($file_params);
        $classes_attendance = array('section' => $selcted_section, 'username' => $currentUsername, 'attendance_row' => $attendance_row ,'time'=>date("l jS \of F Y h:i:s A"));
        file_put_contents($file->getFileUri(), json_encode($classes_attendance));
        $file->save();

        $host = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
        $attended_url = $host."students/attendance/$date/$selcted_class/$selcted_section";
        $this->messenger()->addMessage($this->t('Attendance save successfully!!. Visit this <a href="@Link">@Link</a>',['@Link' => $attended_url]));

    }else{

        $this->messenger()->addMessage($this->t('Please select the class or section !!')); 

    }
  }

  public static function getClassesOptions() {
        return [
          '' => 'Class List', '1' => '1','2' => '2','3' => '3', '4' => '4','5' => '5','6' => '6', '7' => '7','8' => '8','9' => '9' ,'10' => '10','11' => '11','12' => '12'
        ];
  }

  public function getSectionCallback(array $form, FormStateInterface $form_state) {
      return $form['student_section_container'];
  }

  public function getAttendanceSheetCallback(array $form, FormStateInterface $form_state) {
      return $form['student_attendance_container'];
  }

  public function getSections($key = '') {
    switch($key){
        case '1':
          $options = [ "" => "Choose Section", "A" => "A", "B" => "B" ];
          break;

        case '2':
          $options = [ "" => "Choose Section", "A" => "A", "B" => "B" ];
          break;

        case '3':
          $options = [ "" => "Choose Section", "A" => "A", "B" => "B" ];
          break;

        case '4':
          $options = [ "" => "Choose Section", "A" => "A", "B" => "B", "C" => "C" ];
          break;

        case '5':
          $options = [ "" => "Choose Section", "A" => "A", "B" => "B" ];
          break;

        case '6':
          $options = [ "" => "Choose Section", "CP" => "CP", "P" => "P" ];
          break;

        case '7':
          $options = [ "" => "Choose Section", "CS-1" => "CS-1", "CS-2" => "CS-2", "P" => "P" ];
          break;

        case '8':
          $options = [ "" => "Choose Section", "CS" => "CS", "P" => "P" ];
          break;

        case '9':
          $options = [ "" => "Choose Section", "DP" => "DP", "CS" => "CS", "P" => "P", "O" => "O" ];
          break;

        case '10':
          $options = [ "" => "Choose Section", "DP" => "DP", "CS" => "CS", "O" => "O" ];
          break;

        case '11':
          $options = [ "" => "Choose Section", "Mathematics" => "Mathematics", "Biology" => "Biology", "Commerce" => "Commerce", "Arts" => "Arts" ];
          break;

        case '12':
          $options = [ "" => "Choose Section", "Mathematics" => "Mathematics", "Biology" => "Biology", "Commerce" => "Commerce", "Arts" => "Arts", 'CS' => 'CS' ];
          break;

        default:
          $options = [ " " => "Section List"];
          break;
    }
      
     return $options;
  }

}