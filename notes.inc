<?php
// $Id$

/******************************************************************************/
/***************************** BOOK NOTES *************************************/
/******************************************************************************/

function book_notes_form($form_state)
{
  global $user;

  /* get current proposal */
  $preference_id = arg(2);
  $preference_id = (int)$preference_id;
  $result = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $preference_id);
  if ($result)
  {
    if ($row = db_fetch_object($result))
    {
      /* everything ok */
    } else {
      drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
      drupal_goto('code_approval/bulk');
      return;
    }
  } else {
    drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
    drupal_goto('code_approval/bulk');
    return;
  }

  /* get current notes */
  $notes = '';
  $notes_q = db_query("SELECT * FROM {textbook_companion_notes} WHERE preference_id = %d LIMIT 1", $preference_id);
  if ($notes_q)
  {
    $notes_data = db_fetch_object($notes_q);
    $notes = $notes_data->notes;
  }

  $book_details = _book_information($preference_id);
  $form['book_details'] = array(
    '#type' => 'item',
    '#value' => '<span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span><br />' .
      '<strong>Author:</strong> ' . $book_details->preference_author . '<br />' .
      '<strong>Title of the Book:</strong> ' . $book_details->preference_book . '<br />' .
      '<strong>Publisher:</strong> ' . $book_details->preference_publisher . '<br />' .
      '<strong>Year:</strong> ' . $book_details->preference_year . '<br />' .
      '<strong>Edition:</strong> ' . $book_details->preference_edition . '<br /><br />' .
      '<span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span><br />' .
      '<strong>Contributor Name:</strong> ' . $book_details->proposal_full_name . ', ' . $book_details->proposal_course . ', ' . $book_details->proposal_branch . ', ' . $book_details->proposal_university . '<br />',
  );

  $form['notes'] = array(
    '#type' => 'textarea',
    '#rows' => 20,
    '#title' => t('Notes for Reviewers'),
    '#default_value' => $notes,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  $form['cancel'] = array(
    '#type' => 'markup',
    '#value' => l(t('Back'), 'code_approval/bulk'),
  );
  return $form;
}

function book_notes_form_submit($form, &$form_state)
{
  global $user;

  /* get current proposal */
  $preference_id = arg(2);
  $preference_id = (int)$preference_id;
  $result = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $preference_id);
  if ($result)
  {
    if ($row = db_fetch_object($result))
    {
      /* everything ok */
    } else {
      drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
      drupal_goto('code_approval/bulk');
      return;
    }
  } else {
    drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
    drupal_goto('code_approval/bulk');
    return;
  }

  /* find existing notes */
  $notes_q = db_query("SELECT * FROM {textbook_companion_notes} WHERE preference_id = %d LIMIT 1", $preference_id);
  $notes_data = db_fetch_object($notes_q);

  /* add or update notes in database */
  if ($notes_data) {
    db_query("UPDATE {textbook_companion_notes} SET notes = '%s' WHERE id = %d", $form_state['values']['notes'], $notes_data->id);
    drupal_set_message('Notes updated successfully.', 'status');
  } else {
    db_query("INSERT INTO {textbook_companion_notes} (preference_id, notes) VALUES (%d, '%s')", $preference_id, $form_state['values']['notes']);
    drupal_set_message('Notes added successfully.', 'status');
  }
}

/* return proposal and author information */ 
function _book_information($preference_id)
{
  $book_data = db_fetch_object(db_query("SELECT
    preference.book as preference_book, preference.author as preference_author, preference.isbn as preference_isbn, preference.publisher as preference_publisher, preference.edition as preference_edition, preference.year as preference_year,
    proposal.full_name as proposal_full_name, proposal.faculty as proposal_faculty, proposal.reviewer as proposal_reviewer, proposal.course as proposal_course, proposal.branch as proposal_branch, proposal.university as proposal_university
    FROM {textbook_companion_proposal} proposal LEFT JOIN {textbook_companion_preference} preference ON proposal.id = preference.proposal_id WHERE preference.id = %d", $preference_id));
  return $book_data;
}

