<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ContactDetails.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ContactDetails extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_details';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    if (!isset($_REQUEST['msg'])) {
      drupal_set_message('<strong>Caution</strong>:Please update Contact Detail carefully as this will be used for future reference during <strong>Payment</strong></li></ul>', 'error');
    }

    $x = $user->uid;
    /*$query2 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid=".$x);
        $data2 = db_fetch_object($query2);*/

    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $x);
    $result = $query->execute();
    $data2 = $result->fetchObject();


    if (!$data2) {
      drupal_set_message('Fill Up The <a href="proposal">Book Proposal Form</a>', 'error');
      return '';
    }
    /*$query3 = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status=1 AND proposal_id=".$data2->id);
	$data3 = db_fetch_object($query3);*/

    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approval_status', 1);
    $query->condition('proposal_id', $data2->id);
    $result = $query->execute();
    $data3 = $result->fetchObject();

    if (!$data3->approval_status) {
      drupal_set_message('Book Proposal Has Not Been Accpeted .', 'error');
      return '';
    }

    $proposal_id = $data2->id;

    /*$comment_qx = db_query("SELECT * FROM textbook_companion_cheque c WHERE proposal_id =".$proposal_id);
	$commentv = db_fetch_object($comment_qx);*/

    $query = db_select('textbook_companion_cheque', 'c');
    $query->fields('c');
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();
    $commentv = $result->fetchObject();

    $form16 = $commentv->commentf;
    $mob_no = $data2->mobile;
    $full_name = $data2->full_name;

    /*$query1 = db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id=".$proposal_id);*/
    $query = db_select('textbook_companion_cheque');
    $query->fields('textbook_companion_cheque');
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();

    $form1 = 0;
    $form2 = 0;
    $form3 = 0;
    $form4 = 0;
    $form5 = 0;
    $form6 = 0;
    $form7 = 0;
    $form8 = 0;
    $form9 = 0;
    $form10 = 0;
    $form11 = 0;
    $form12 = 0;
    $form13 = 0;
    $form14 = 0;
    $form15 = 0;

    if ($data = $result->fetchObject()) {
      $form1 = $data->address;
      $form8 = $data->alt_mobno;
      $form9 = $data->perm_city;
      $form10 = $data->perm_state;
      $form11 = $data->perm_pincode;
      $form12 = $data->temp_chq_address;
      $form13 = $data->temp_city;
      $form14 = $data->temp_state;
      $form15 = $data->temp_pincode;
    }
    else {
      /*db_query("insert into {textbook_companion_cheque} (proposal_id) values(%d)",$proposal_id);*/

      $query = "insert into {textbook_companion_cheque} (proposal_id) values (:proposal_id)";
      $args = [":proposal_id" => $proposal_id];
      $result = db_query($query, $args, ['return' => Database::RETURN_INSERT_ID]);
    }
    $form['candidate_detail'] = [
      '#type' => 'fieldset',
      '#value' => $form_html,
      '#title' => t('Candidate Detail'),
      '#attributes' => [
        'id' => 'candidate_detail'
        ],
    ];
    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#default_value' => $proposal_id,
    ];
    $form['candidate_detail']['fullname'] = [
      '#type' => 'textfield',
      '#title' => t('Full Name'),
      '#size' => 48,
      '#default_value' => $full_name,
    ];
    $form['candidate_detail']['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#size' => 48,
      '#value' => $user->mail,
      '#disabled' => TRUE,
    ];
    $form['candidate_detail']['mobileno1'] = [
      '#type' => 'textfield',
      '#title' => t('Mobile No'),
      '#size' => 48,
      '#default_value' => $mob_no,
    ];

    $form['candidate_detail']['mobileno2'] = [
      '#type' => 'textfield',
      '#title' => t('Alternate Mobile No'),
      '#size' => 48,
      '#default_value' => $form8,
    ];

    /*$chq_q=db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id=".$proposal_id);
        $chq_data=db_fetch_object($chq_q);*/

    $query = db_select('textbook_companion_cheque');
    $query->fields('textbook_companion_cheque');
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();
    $chq_data = $result->fetchObject();


    /*$q_form = db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id=".$proposal_id);
        $q_data = db_fetch_object($q_form);*/

    $query = db_select('textbook_companion_paper');
    $query->fields('textbook_companion_paper');
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();
    $q_data = $result->fetchObject();

    $form_html .= '<ul>';
    if ($q_data->internship_form) {
      $form_html .= '<li><strong>Internship Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Internship Application </strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
    }
    if ($q_data->copyright_form) {
      $form_html .= '<li><strong>Copyright Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Copyright Application</strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
    }
    if ($q_data->undertaking_form) {
      $form_html .= '<li><strong>Undertaking Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Undertaking Application</strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
    }
    $form_html .= '</ul>';
    $form['Application Status'] = [
      '#type' => 'fieldset',
      '#value' => $form_html,
      '#title' => t('Application Form Status'),
      '#attributes' => [
        'id' => 'app_status'
        ],
    ];
    $form['perm_cheque_address'] = [
      '#type' => 'fieldset',
      '#title' => t('Permanent Address'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'perm_cheque_address'
        ],
    ];
    $form['temp_cheque_address'] = [
      '#type' => 'fieldset',
      '#title' => t('Temporary Address'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'temp_cheque_address'
        ],
    ];
    $form['perm_cheque_address']['chq_address'] = [
      '#type' => 'textarea',
      '#title' => t('Address'),
      '#size' => 35,
      '#default_value' => $form1,
    ];
    $form['perm_cheque_address']['perm_city'] = [
      '#type' => 'textfield',
      '#default_value' => $form9,
      '#title' => t('City'),
      '#size' => 35,
    ];
    $form['perm_cheque_address']['perm_state'] = [
      '#type' => 'textfield',
      '#default_value' => $form10,
      '#title' => t('State'),
      '#size' => 35,
    ];
    $form['perm_cheque_address']['perm_pincode'] = [
      '#type' => 'textfield',
      '#default_value' => $form11,
      '#title' => t('Zip code'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_chq_address'] = [
      '#type' => 'textarea',
      '#default_value' => $form12,
      '#title' => t('Address'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_city'] = [
      '#type' => 'textfield',
      '#default_value' => $form13,
      '#title' => t('City'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_state'] = [
      '#type' => 'textfield',
      '#default_value' => $form14,
      '#title' => t('State'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_pincode'] = [
      '#type' => 'textfield',
      '#default_value' => $form15,
      '#title' => t('Zip code'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['same_address'] = [
      '#type' => 'checkbox',
      '#title' => t('Same As Permanent Address'),
    ];
    if ($chq_data->commentf) {
      $form['commentu'] = [
        '#type' => 'fieldset',
        '#title' => t('Remarks'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#attributes' => [
          'id' => 'comment_cheque'
          ],
      ];
      $form['commentu']['comment_cheque'] = [
        '#type' => 'textarea',
        '#size' => 35,
        '#default_value' => $form16,
      ];
    }
    $form['commentu']['comment_cheque'] ['#attributes']['readonly'] = 'readonly';
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#value' => l(t('Cancel'), 'manage_proposal/all'),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $user = \Drupal::currentUser();
    $x = $user->uid;

    /*$query2 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid=".$x);
	$data2 = db_fetch_object($query2);*/

    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $x);
    $result = $query->execute();
    $data2 = $result->fetchObject();


    /*$query ="UPDATE {textbook_companion_cheque} SET 
	alt_mobno = '".$form_state['values']['mobileno2']."' , 
	address = '".$form_state['values']['chq_address']."',  
	perm_city = '".$form_state['values']['perm_city']."', 
	perm_state = '".$form_state['values']['perm_state']."', 
	perm_pincode = '".$form_state['values']['perm_pincode']."', 
	temp_chq_address = '".$form_state['values']['temp_chq_address']."', 
	temp_city = '".$form_state['values']['temp_city']."', 
	temp_state = '".$form_state['values']['temp_state']."', 
	temp_pincode = '".$form_state['values']['temp_pincode']."' ,
	address_con = 'Submitted'
	WHERE proposal_id = ".$data2->id;
	db_query($query);*/

    $query = db_update('textbook_companion_cheque');
    $query->fields([
      'alt_mobno' => $form_state[ values ][ mobileno2 ],
      'address' => $form_state[ values ][ chq_address ],
      'perm_city' => $form_state[ values ][ perm_city ],
      'perm_state' => $form_state[ values ][ perm_state ],
      'perm_pincode' => $form_state[ values ][ perm_pincode ],
      'temp_chq_address' => $form_state[ values ][ temp_chq_address ],
      'temp_city' => $form_state[ values ][ temp_city ],
      'temp_state' => $form_state[ values ][ temp_state ],
      'temp_pincode' => $form_state[ values ][ temp_pincode ],
      'address_con' => 'Submitted',
    ]);
    $query->condition('proposal_id', $data2->id);
    $num_updated = $query->execute();

    drupal_set_message('Contact Details Has Been Updated.....!', 'status');
    drupal_goto('mycontact', ['msg' => 0], $fragment = NULL, $http_response_code = 302);
  }

}
?>
