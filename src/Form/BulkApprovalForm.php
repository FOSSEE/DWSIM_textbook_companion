<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\BulkApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class BulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = _bulk_list_of_books();
    $options_two = _ajax_bulk_get_chapter_list();
    $selected = !$form_state->getValue(['book']) ? $form_state->getValue([
      'book'
      ]) : key($options_first);
    $select_two = !$form_state->getValue(['chapter']) ? $form_state->getValue([
      'chapter'
      ]) : key($options_two);
    $form['book'] = [
      '#type' => 'select',
      '#title' => t('Title of the Book'),
      '#options' => _bulk_list_of_books(),
      '#default_value' => $selected,
      '#tree' => TRUE,
      '#ajax' => [
        'callback' => 'ajax_bulk_chapter_list_callback'
        ],
      '#validated' => TRUE,
    ];
    $form['download_book'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_book"></div>',
    ];
    $form['notes_book'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_book_notes"></div>',
    ];
    $form['book_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for selected book'),
      '#options' => _bulk_list_book_actions(),
      '#prefix' => '<div id="ajax_selected_book_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],
      '#validated' => TRUE,
    ];
    $form['chapter'] = [
      '#type' => 'select',
      '#title' => t('Title of the Chapter'),
      '#options' => _ajax_bulk_get_chapter_list($selected),
      '#prefix' => '<div id="ajax_select_chapter_list">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
      '#tree' => TRUE,
      '#ajax' => [
        'callback' => 'ajax_bulk_example_list_callback'
        ],
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => ['value' => 0]
          ]
        ],
    ];
    $form['download_chapter'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_download_chapter"></div>',
    ];
    $form['chapter_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for selected chapter'),
      '#options' => _bulk_list_chapter_actions(),
      '#prefix' => '<div id="ajax_selected_chapter_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],
      '#ajax' => ['callback' => 'ajax_bulk_chapter_actions_callback'],
    ];
    $form['example'] = [
      '#type' => 'select',
      '#title' => t('Example No. (Caption)'),
      '#options' => _ajax_bulk_get_examples(),
      // '#default_value' => $example_default_value,       
		'#validated' => TRUE,
      '#prefix' => '<div id="ajax_selected_example">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],
      '#ajax' => ['callback' => 'ajax_bulk_example_files_callback'],
    ];
    $form['download_example'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_download_selected_example"></div>',
    ];
    $form['edit_example'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_edit_selected_example"></div>',
    ];
    $form['example_files'] = [
      '#type' => 'item',
      '#markup' => '',
      '#prefix' => '<div id="ajax_example_files_list">',
      '#suffix' => '</div>',
    ];
    $form['example_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for selected example'),
      '#options' => _bulk_list_example_actions(),
      '#prefix' => '<div id="ajax_selected_example_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
      '#states' => [
        'visible' => [
          [
            [
              ':input[name="book_actions"]' => [
                'value' => 3
                ]
              ],
            'or',
            [':input[name="chapter_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="example_actions"]' => [
                'value' => 3
                ]
              ],
            'or',
            [':input[name="book_actions"]' => ['value' => 4]],
          ]
          ],
        'required' => [
          [
            [':input[name="book_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="chapter_actions"]' => [
                'value' => 3
                ]
              ],
            'or',
            [':input[name="example_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="book_actions"]' => [
                'value' => 4
                ]
              ],
          ]
          ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $root_path = textbook_companion_path();
    if ($form_state->get(['clicked_button', '#value']) == 'Submit') {
      if ($form_state->getValue(['book'])) {
        del_book_pdf($form_state->getValue(['book']));
      }
      if (user_access('bulk manage code')) {
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $form_state->getValue(['book']));
        $result = $query->execute();
        $pref_data = $result->fetchObject();
        $prop_id = $pref_data->proposal_id;
        $query = db_select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $prop_id);
        $user_query = $query->execute();
        $user_info = $user_query->fetchObject();
        $user_data = user_load($user_info->uid);
        if (($form_state->getValue(['book_actions']) == 1) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 0)) {
          /* approving entire book */
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          $query = db_select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $chapter_q = $query->execute();
          while ($chapter_data = $chapter_q->fetchObject()) {
            $query = db_update('textbook_companion_example');
            $query->fields([
              'approval_status' => 1,
              'approver_uid' => $user->uid,
            ]);
            $query->condition('chapter_id', $chapter_data->id);
            $query->condition('approval_status', 0);
            $num_updated = $query->execute();
          } //$chapter_data = $chapter_q->fetchObject()
          $query = db_update('textbook_companion_preference');
          $query->fields(['submited_all_examples_code' => 2]);
          $query->condition('id', $form_state->getValue(['book']));
          $num_updated = $query->execute();
          drupal_set_message(t('Approved Entire Book.'), 'status');
          /* email */
          $email_subject = t('[!site_name][Textbook Companion] Your uploaded Textbook Companion examples have been approved', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your all the uploaded examples for the book have been approved.

Title of the book : ' . $preference_data->book . '
Author name : ' . $preference_data->author . '
ISBN No. : ' . $preference_data->isbn . '
Publisher and Place : ' . $preference_data->publisher . '
Edition : ' . $preference_data->edition . '
Year of publication : ' . $preference_data->year . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        } //($form_state['values']['book_actions'] == 1) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)
        elseif (($form_state->getValue(['book_actions']) == 2) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 0)) {
          /* pending entire book */
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          $query = db_select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $chapter_q = $query->execute();
          while ($chapter_data = $chapter_q->fetchObject()) {
            $query = db_update('textbook_companion_example');
            $query->fields(['approval_status' => 0]);
            $query->condition('chapter_id', $chapter_data->id);
            $num_updated = $query->execute();
          } //$chapter_data = $chapter_q->fetchObject()
          drupal_set_message(t('Pending Review Entire Book.'), 'status');
          /* email */
          $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as pending', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your all the uploaded examples for the book have been marked as pending to be reviewed. 
You will be able to see the examples after they have been approved by one of our reviewers.

Title of the book : ' . $preference_data->book . '
Author name : ' . $preference_data->author . '
ISBN No. : ' . $preference_data->isbn . '
Publisher and Place : ' . $preference_data->publisher . '
Edition : ' . $preference_data->edition . '
Year of publication : ' . $preference_data->year . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        } //($form_state['values']['book_actions'] == 2) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)
        elseif (($form_state->getValue(['book_actions']) == 3) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 0)) {
          if (strlen(trim($form_state->getValue(['message']))) <= 30) {
            $form_state->setErrorByName('message', t(''));
            drupal_set_message("Please mention the reason for disapproval. Minimum 30 character required", 'error');
            return;
          } //strlen(trim($form_state['values']['message'])) <= 30
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          if (!user_access('bulk delete code')) {
            drupal_set_message(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Book.'), 'error');
            return;
          } //!user_access('bulk delete code')
          if (delete_book($form_state->getValue(['book']))) {
            drupal_set_message(t('Dis-Approved and Deleted Entire Book.'), 'status');
          } //delete_book($form_state['values']['book'])
          else {
            drupal_set_message(t('Error Dis-Approving and Deleting Entire Book.'), 'error');
          }
          /* email */
          $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as
				dis-approved', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your all the uploaded examples for the whole book have been marked as dis-approved.

Title of the book : ' . $preference_data->book . '
Author name : ' . $preference_data->author . '
ISBN No. : ' . $preference_data->isbn . '
Publisher and Place : ' . $preference_data->publisher . '
Edition : ' . $preference_data->edition . '
Year of publication : ' . $preference_data->year . '


Reason for dis-approval:' . $form_state->getValue(['message']) . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        }
        elseif (($form_state->getValue(['book_actions']) == 4) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 0)) {
          if (strlen(trim($form_state->getValue(['message']))) <= 30) {
            $form_state->setErrorByName('message', t(''));
            drupal_set_message("Please mention the reason for disapproval/deletion. Minimum 30 character required", 'error');
            return;
          } //strlen(trim($form_state['values']['message'])) <= 30
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          if (!user_access('bulk delete code')) {
            drupal_set_message(t('You do not have permission to Bulk Delete Entire Book Including Proposal.'), 'error');
            return;
          } //!user_access('bulk delete code')
				/* check if dependency files are present */
          if (delete_book($form_state->getValue(['book']))) {
            drupal_set_message(t('Dis-Approved and Deleted Entire Book examples.'), 'status');
            $dir_path = $root_path . $form_state->getValue(['book']);
            if (is_dir($dir_path)) {
              $res = rmdir($dir_path);
              if (!$res) {
                drupal_set_message(t("Cannot delete Book directory : " . $dir_path . ". Please contact administrator."), 'error');
                return;
              } //!$res
            } //is_dir($dir_path)
            else {
              drupal_set_message(t("Book directory not present : " . $dir_path . ". Skipping deleting book directory."), 'status');
            }
            /* deleting preference and proposal */
            $query = db_select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('id', $form_state->getValue(['book']));
            $result = $query->execute();
            $preference_data = $result->fetchObject();
            $proposal_id = $preference_data->proposal_id;
            $query = db_delete('textbook_companion_preference');
            $query->condition('proposal_id', $proposal_id);
            $num_deleted = $query->execute();
            $query = db_delete('textbook_companion_proposal');
            $query->condition('id', $proposal_id);
            $num_deleted = $query->execute();
            drupal_set_message(t('Deleted Book Proposal.'), 'status');
            /* email */
            $email_subject = t('[!site_name] Your uploaded Textbook Companion examples including the book proposal 					have been deleted', [
              '!site_name' => variable_get('site_name', '')
              ]);
            $email_body = [
              0 => t('

Dear !user_name,

We regret to inform you that all the uploaded examples including the book with following details have been deleted permanently.


Title of the book : ' . $pref_data->book . '
Author name : ' . $pref_data->author . '
ISBN No. : ' . $pref_data->isbn . '
Publisher and Place : ' . $pref_data->publisher . '
Edition : ' . $pref_data->edition . '
Year of publication : ' . $pref_data->year . '

Reason for deletion:' . $form_state->getValue(['message']) . '


Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
                '!site_name' => variable_get('site_name', ''),
                '!user_name' => $user_data->name,
              ])
              ];
          } //delete_book($form_state['values']['book'])
          else {
            drupal_set_message(t('Error Dis-Approving and Deleting Entire Book.'), 'error');
          }
        } //($form_state['values']['book_actions'] == 4) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 1) && ($form_state->getValue(['example_actions']) == 0)) {
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = db_select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = db_update('textbook_companion_example');
          $query->fields([
            'approval_status' => 1,
            'approver_uid' => $user->uid,
          ]);
          $query->condition('chapter_id', $form_state->getValue(['chapter']));
          $query->condition('approval_status', 0);
          $num_updated = $query->execute();
          drupal_set_message(t('Approved Entire Chapter.'), 'status');
          /* email */
          $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been approved', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your all the uploaded examples for the chapter have been approved. 

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        } //($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 1) && ($form_state['values']['example_actions'] == 0)
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 2) && ($form_state->getValue(['example_actions']) == 0)) {
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = db_select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = db_update('textbook_companion_example');
          $query->fields(['approval_status' => 0]);
          $query->condition('chapter_id', $form_state->getValue(['chapter']));
          $num_updated = $query->execute();
          drupal_set_message(t('Entire Chapter marked as Pending Review.'), 'status');
          /* email */
          $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as pending', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your all the uploaded examples for the chapter have been marked as pending to be reviewed.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        } //($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 2) && ($form_state['values']['example_actions'] == 0)
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 3) && ($form_state->getValue(['example_actions']) == 0)) {
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = db_select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          if (strlen(trim($form_state->getValue(['message']))) <= 30) {
            $form_state->setErrorByName('message', t(''));
            drupal_set_message("Please mention the reason for disapproval. Minimum 30 character required", 'error');
            return;
          } //strlen(trim($form_state['values']['message'])) <= 30
          if (!user_access('bulk delete code')) {
            drupal_set_message(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Chapter.'), 'error');
            return;
          } //!user_access('bulk delete code')
          if (delete_chapter($form_state->getValue(['chapter']))) {
            drupal_set_message(t('Dis-Approved and Deleted Entire Chapter.'), 'status');
          } //delete_chapter($form_state['values']['chapter'])
          else {
            drupal_set_message(t('Error Dis-Approving and Deleting Entire Chapter.'), 'error');
          }
          /* email */
          $email_subject = t('[!site_name] Your uploaded Textbook Companion example have been marked as 					dis-approved', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your uploaded example for the entire chapter have been marked as dis-approved.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '


Reason for dis-approval:' . $form_state->getValue(['message']) . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        } //($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 3) && ($form_state['values']['example_actions'] == 0)
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 1)) {
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = db_select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = db_select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $form_state->getValue(['example']));
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          $query = db_update('textbook_companion_example');
          $query->fields([
            'approval_status' => 1,
            'approver_uid' => $user->uid,
          ]);
          $query->condition('id', $form_state->getValue(['example']));
          $num_updated = $query->execute();
          drupal_set_message(t('Example approved.'), 'status');
          /* email */
          $email_subject = t('[!site_name] Your uploaded Textbook Companion example have been approved', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your example for DWSIM Textbook Companion with the following details is approved.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '
Example number : ' . $examp_data->number . '
Caption : ' . $examp_data->caption . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        } //($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 1)
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 2)) {
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = db_select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = db_select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $form_state->getValue(['example']));
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          $query = db_update('textbook_companion_example');
          $query->fields(['approval_status' => 0]);
          $query->condition('id', $form_state->getValue(['example']));
          $num_updated = $query->execute();
          drupal_set_message(t('Example marked as Pending Review.'), 'status');
          /* email */
          $email_subject = t('[!site_name] Your uploaded Textbook Companion example has been marked as pending', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your uploaded example for DWSIM Textbook Companion with the following details has been marked as pending to be reviewed.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '
Example number : ' . $examp_data->number . '
Caption : ' . $examp_data->caption . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        } //($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 2)
        elseif (($form_state->getValue(['book_actions']) == 0) && ($form_state->getValue(['chapter_actions']) == 0) && ($form_state->getValue(['example_actions']) == 3)) {
          if (strlen(trim($form_state->getValue(['message']))) <= 30) {
            $form_state->setErrorByName('message', t(''));
            drupal_set_message("Please mention the reason for disapproval. Minimum 30 character required", 'error');
            return;
          } //strlen(trim($form_state['values']['message'])) <= 30
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $form_state->getValue(['book']));
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          $query = db_select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $form_state->getValue(['book']));
          $query->condition('id', $form_state->getValue(['chapter']));
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          $query = db_select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $form_state->getValue(['example']));
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          if (delete_example($form_state->getValue(['example']))) {
            drupal_set_message(t('Example Dis-Approved and Deleted.'), 'status');
          } //delete_example($form_state['values']['example'])
          else {
            drupal_set_message(t('Error Dis-Approving and Deleting Example.'), 'error');
          }
          /* email */
          $email_subject = t('[!site_name] Your uploaded Textbook Companion example has been marked as 
				dis-approved', [
            '!site_name' => variable_get('site_name', '')
            ]);
          $email_body = [
            0 => t('

Dear !user_name,

Your example for DWSIM Textbook Companion has been marked as dis-approved and deleted.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '
Example number : ' . $examp_data->number . '
Caption : ' . $examp_data->caption . '

Reason for dis-approval:' . $form_state->getValue(['message']) . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', [
              '!site_name' => variable_get('site_name', ''),
              '!user_name' => $user_data->name,
            ])
            ];
        } //($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 3)
        else {
          drupal_set_message(t('Please select only one action at a time'), 'error');
          return;
        }
        /****** sending email when everything done ******/
        if ($email_subject) {
          $email_to = $user_data->mail;
          $from = variable_get('textbook_companion_from_email', '');
          $bcc = variable_get('textbook_companion_emails', '');
          $cc = variable_get('textbook_companion_cc_emails', '');
          $params['standard']['subject'] = $email_subject;
          $params['standard']['body'] = $email_body;
          $params['standard']['headers'] = [
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc,
          ];
          if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $params, $from, TRUE)) {
            drupal_set_message('Error sending email message.', 'error');
          }
        } //$email_subject
      } //user_access('bulk manage code')
      else {
        drupal_set_message(t('You do not have permission to bulk manage code.'), 'error');
      }
    } //$form_state['clicked_button']['#value'] == 'Submit'
  }

}
?>
