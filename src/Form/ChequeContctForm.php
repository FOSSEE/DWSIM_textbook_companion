<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ChequeContctForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ChequeContctForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cheque_contct_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    /*$preference4_q = db_query("SELECT id FROM {textbook_companion_proposal} WHERE uid=".$user->uid);*/

    $query = db_select('textbook_companion_proposal');
    $query->fields('id', ['']);
    $query->condition('uid', $user->uid);
    $result = $query->execute();
    $data = $result->fetchObject();

    $form1 = $data->id;

    if ($user->uid) {
      $form['#redirect'] = FALSE;

      $form['search'] = [
        '#type' => 'textfield',
        '#title' => t('Search'),
        '#size' => 48,
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Search'),
      ];

      $form['cancel'] = [
        '#type' => 'markup',
        '#value' => l(t('Cancel'), ''),
      ];

      $form['submit2'] = [
        '#type' => 'markup',
        '#value' => l(t('Generate Report'), 'cheque_contct/report'),
        '#attributes' => [
          'id' => 'perm_report'
          ],
      ];

      /*$search_q = db_query("SELECT * FROM textbook_companion_proposal p,textbook_companion_cheque c WHERE c.address_con = 'Submitted' AND (p.id = c.proposal_id)");*/
      $query = db_select('textbook_companion_proposal', 'p');
      $query->join('textbook_companion_cheque', 'c', 'p.id = c.proposal_id');
      $query->fields('p', ['textbook_companion_proposal']);
      $query->fields('c', ['textbook_companion_cheque']);
      $query->condition('c.address_con', 'Submitted');
      $result = $query->execute();

      while ($search_data = $result->fetchObject()) {
        $search_rows[] = [
          l($search_data->full_name, 'cheque_contct/status/' . $search_data->proposal_id),
          $search_data->address_con,
          $search_data->cheque_no,
          $search_data->cheque_dispatch_date,
        ];
      }
      if ($search_rows) {
        $search_header = [
          'Name Of The Student',
          'Application Form Status',
          'Cheque No',
          'Cheque Clearance Date',
        ];
        $output .= theme('table', [
          'headers' => $search_header,
          'rows' => $search_rows,
        ]);
        $form['search_results'] = [
          '#type' => 'item',
          '#title' => $_POST['search'],
          '#markup' => $output,
        ];
      }
      else {
        $form['search_results'] = [
          '#type' => 'item',
          '#title' => t('Search results for "') . $_POST['search'] . '"',
          '#markup' => 'No results found',
        ];
      }
      if ($_POST) {
        $output = '';
        $search_rows = [];
        $search_quert = '';

        /*$search_q = db_query("SELECT * FROM textbook_companion_proposal p,textbook_companion_cheque c WHERE c.address_con = 'Submitted' AND (p.id = c.proposal_id) AND (p.full_name LIKE '%%%s%%')", $_POST['search']);*/
        $query = db_select('textbook_companion_proposal', 'p');
        $query->join('textbook_companion_cheque', 'c', 'p.id = c.proposal_id');
        $query->fields('p', ['textbook_companion_proposal']);
        $query->fields('c', ['textbook_companion_cheque']);
        $query->condition('c.address_con', 'Submitted');
        $query->condition('p.full_name', '%%' . $_POST['search'] . '%%', 'LIKE');
        $result = $query->execute();


        while ($search_data = $result->fetchObject()) {
          $search_rows[] = [
            l($search_data->full_name, 'cheque_contct/status/' . $search_data->proposal_id),
            $search_data->address_con,
            $search_data->cheque_no,
            $search_data->cheque_dispatch_date,
          ];
        }
        if ($search_rows) {
          $search_header = [
            'Name Of The Student',
            'Application Form Status',
            'Cheque No',
            'Cheque Clearance Date',
          ];
          $output .= theme('table', [
            'headers' => $search_header,
            'rows' => $search_rows,
          ]);
          $form['search_results'] = [
            '#type' => 'item',
            '#title' => t('Search results for "') . $_POST['search'] . '"',
            '#markup' => $output,
          ];
        }
        else {
          $form['search_results'] = [
            '#type' => 'item',
            '#title' => t('Search results for "') . $_POST['search'] . '"',
            '#markup' => 'No results found',
          ];
        }
      }
      return $form;
    }
    else {
      /*$preference5_q = db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id=".$form1);
		$data1 = db_fetch_object($preference5_q);*/
      $query = db_select('textbook_companion_paper');
      $query->fields('textbook_companion_paper');
      $query->condition('proposal_id', $form1);
      $result = $query->execute();
      $data1 = $result->fetchObject();

      $form2 = $data1->internship_form;
      $form3 = $data1->copyright_form;
      $form4 = $data1->undertaking_form;
      $form5 = $data1->reciept_form;

      /*$chq_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id=".$form1);
		$data_chq = db_fetch_object($chq_q);*/

      $query = db_select('textbook_companion_proposal');
      $query->fields('textbook_companion_proposal');
      $query->condition('id', $form1);
      $result = $query->execute();
      $data_chq = $result->fetchObject();

      $form9 = $data_chq->full_name;
      $form8 = $data->how_project;
      $form10 = $data_chq->mobile;
      $form11 = $data_chq->course;
      $form12 = $data_chq->branch;
      $form13 = $data_chq->university;
      if ($form2 && $form3 && $form4 && $form5) {
        $form['full_name'] = [
          '#type' => 'textfield',
          '#title' => t('Full Name'),
          '#size' => 30,
          '#maxlength' => 50,
          '#default_value' => $form9,
        ];
        $form['mobile'] = [
          '#type' => 'textfield',
          '#title' => t('Mobile No.'),
          '#size' => 30,
          '#maxlength' => 15,
          '#default_value' => $form10,
        ];
        $form['how_project'] = [
          '#type' => 'select',
          '#title' => t('How did you come to know about this project'),
          '#options' => [
            'eSim Website' => 'eSim Website',
            'Friend' => 'Friend',
            'Professor/Teacher' => 'Professor/Teacher',
            'Mailing List' => 'Mailing List',
            'Poster in my/other college' => 'Poster in my/other college',
            'Others' => 'Others',
          ],
          '#default_value' => $form8,
        ];
        $form['course'] = [
          '#type' => 'textfield',
          '#title' => t('Course'),
          '#size' => 30,
          '#maxlength' => 50,
          '#default_value' => $form11,
        ];
        $form['branch'] = [
          '#type' => 'select',
          '#title' => t('Department/Branch'),
          '#options' => [
            'Electrical Engineering' => 'Electrical Engineering',
            'Electronics Engineering' => 'Electronics Engineering',
            'Computer Engineering' => 'Computer Engineering',
            'Chemical Engineering' => 'Chemical Engineering',
            'Instrumentation Engineering' => 'Instrumentation Engineering',
            'Mechanical Engineering' => 'Mechanical Engineering',
            'Civil Engineering' => 'Civil Engineering',
            'Physics' => 'Physics',
            'Mathematics' => 'Mathematics',
            'Others' => 'Others',
          ],
          '#default_value' => $form12,
        ];

        $form['university'] = [
          '#type' => 'textfield',
          '#title' => t('University/Institute'),
          '#size' => 30,
          '#maxlength' => 100,
          '#default_value' => $form13,
        ];
        $form['addressforcheque'] = [
          '#type' => 'textfield',
          '#title' => t('Address For Mailing Cheque'),
          //'#required' => TRUE,
				'#size' => 30,
          '#maxlength' => 100,
        ];
        $form['submit'] = [
          '#type' => 'submit',
          '#value' => t('Submit'),
        ];
        $form['cancel'] = [
          '#type' => 'markup',
          '#value' => t('Cancel'),
        ];
      }
      if (!$form2) {
        drupal_set_message(t('Internship Form has not been recieved.'), 'error');
      }
      if (!$form3) {
        drupal_set_message(t('Copyright Form has not been recieved.'), 'error');
      }
      if (!$form4) {
        drupal_set_message(t('Undertaking Form has not been recieved.'), 'error');
      }
      return $form;
    }
  }

      public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}


}
?>
