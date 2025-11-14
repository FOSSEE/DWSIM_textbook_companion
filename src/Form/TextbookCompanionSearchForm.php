<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionSearchForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class TextbookCompanionSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_search_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['#redirect'] = FALSE;
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => t('Search'),
      '#size' => 48,
    ];
    $form['search_by_title'] = [
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#title' => t('Search by Title of the Book'),
    ];
    $form['search_by_author'] = [
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#title' => t('Search by Author of the Book'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => l(t('Cancel'), ''),
    ];
    if ($_POST) {
      $output = '';
      $search_rows = [];
      $search_query = '';
      if ($_POST['search_by_title'] && $_POST['search_by_author']) {
        /*$search_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 AND (book LIKE '%%%s%%' OR author LIKE '%%%s%%')", $_POST['search'], $_POST['search']);*/
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('approval_status', 1);
        $or = db_or();
        $or->condition('book', '%%' . $_POST['search'] . '%%', 'LIKE');
        $or->condition('author', '%%' . $_POST['search'] . '%%', 'LIKE');
        $query->condition($or);
        $search_q = $query->execute();
      }
      else {
        if ($_POST['search_by_title']) {
          /*$search_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 AND book LIKE '%%%s%%'", $_POST['search']);*/
          $query = db_select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('approval_status', 1);
          $query->condition('book', '%%' . $_POST['search'] . '%%', 'LIKE');
          $search_q = $query->execute();
        }
        else {
          if ($_POST['search_by_author']) {
            /*$search_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 AND author LIKE '%%%s%%'", $_POST['search']);*/
            $query = db_select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('approval_status', 1);
            $query->condition('author', '%%' . $_POST['search'] . '%%', 'LIKE');
            $search_q = $query->execute();
          }
          else {
            drupal_set_message('Please select whether to search by Title and/or Author of the Book.', 'error');
          }
        }
      }
      while ($search_data = $search_q->fetchObject()) {
        $search_rows[] = [
          l($search_data->book, 'textbook_run/' . $search_data->id),
          $search_data->author,
        ];
      }
      if ($search_rows) {
        $search_header = [
          'Title of the Book',
          'Author Name',
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

    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}
}
?>
