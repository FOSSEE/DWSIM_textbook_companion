<?php
// $Id$

function list_chapters()
{
  global $user;

  /************************ start approve book details ************************/
  $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
  $proposal_data = db_fetch_object($proposal_q);
  if (!$proposal_data)
  {
    drupal_set_message("Please submit a " . l('proposal', 'proposal') . ".", 'error');
    drupal_goto('');
  }
  if ($proposal_data->proposal_status  != 1)
  {
    switch ($proposal_data->proposal_status )
    {
    case 0:
      drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
      drupal_goto('');
      return;
      break;
    case 2:
      drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');
      drupal_goto('');
      return;
      break;
    case 3:
      drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'proposal') . '.'), 'status');
      drupal_goto('');
      return;
      break;
    default:
       drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
       drupal_goto('');
       return;
       break;
    }
  }

  $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
  $preference_data = db_fetch_object($preference_q);
  if (!$preference_data)
  {
    drupal_set_message(t('Invalid Book Preference status. Please contact site administrator for further information.'), 'error');
    drupal_goto('');
    return;
  }
  /************************ end approve book details **************************/

  $return_html = '<br />';
  $return_html .= '<strong>Title of the Book:</strong><br />' . $preference_data->book . '<br /><br />';
  $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->full_name . '<br /><br />';
  $return_html .= l('Upload Example Code', 'textbook_companion/code/upload') . '<br />';

  /* get chapter list */
  $chapter_rows = array();
  $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_data->id);
  while ($chapter_data = db_fetch_object($chapter_q))
  {
    /* get example list */
    $example_q = db_query("SELECT count(*) as example_count FROM {textbook_companion_example} WHERE chapter_id = %d", $chapter_data->id);
    $example_data = db_fetch_object($example_q);
    $chapter_rows[] = array($chapter_data->number, $chapter_data->name . ' (' . l('Edit', 'textbook_companion/code/chapter/edit/' . $chapter_data->id) . ')', $example_data->example_count, l('View', 'textbook_companion/code/list_examples/' . $chapter_data->id));
  }

  /* check if there are any chapters */                                   
  if (!$chapter_rows)
  {
    drupal_set_message(t('No uploads found.'), 'status');
    return $return_html;
  }

  $chapter_header = array('Chapter No.', 'Title of the Chapter', 'Uploaded Examples', 'Actions');
  $return_html .= theme_table($chapter_header, $chapter_rows);
  return $return_html; 
}

function list_examples()
{
  global $user;

  /************************ start approve book details ************************/
  $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
  $proposal_data = db_fetch_object($proposal_q);
  if (!$proposal_data)
  {
    drupal_set_message("Please submit a " . l('proposal', 'proposal') . ".", 'error');
    drupal_goto('');
  }
  if ($proposal_data->proposal_status  != 1)
  {
    switch ($proposal_data->proposal_status )
    {
    case 0:
      drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
      drupal_goto('');
      return;
      break;
    case 2:
      drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');
      drupal_goto('');
      return;
      break;
    case 3:
      drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'proposal') . '.'), 'status');
      drupal_goto('');
      return;
      break;
    default:
       drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
       drupal_goto('');
       return;
       break;
    }
  }

  $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
  $preference_data = db_fetch_object($preference_q);
  if (!$preference_data)
  {
    drupal_set_message(t('Invalid Book Preference status. Please contact site administrator for further information.'), 'error');
    drupal_goto('');
    return;
  }
  /************************ end approve book details **************************/

  /* get chapter details */
  $chapter_id = arg(3);
  $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d LIMIT 1", $chapter_id, $preference_data->id);
  if ($chapter_data = db_fetch_object($chapter_q))
  {
    $return_html = '<br />';
    $return_html .= '<strong>Title of the Book:</strong><br />' . $preference_data->book . '<br /><br />';
    $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->full_name . '<br /><br />';
    $return_html .= '<strong>Chapter Number:</strong><br />' . $chapter_data->number . '<br /><br />';
    $return_html .= '<strong>Title of the Chapter:</strong><br />' . $chapter_data->name . '<br />';
  } else {
    drupal_set_message(t('Invalid chapter.'), 'error');
    drupal_goto('textbook_companion/code');
    return;
  }

  $return_html .= '<br />' . l('Back to Chapter List', 'textbook_companion/code');

  /* get example list */
  $example_rows = array();
  $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d ORDER BY
    CAST(SUBSTRING_INDEX(number, '.', 1) AS BINARY) ASC,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', 2), '.', -1) AS UNSIGNED) ASC,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', -1), '.', 1) AS UNSIGNED) ASC", $chapter_id);
  while ($example_data = db_fetch_object($example_q))
  {
    /* approval status */
    $approval_status = '';
    switch ($example_data->approval_status)
    {
    case 0: $approval_status = 'Pending'; break;
    case 1: $approval_status = 'Approved'; break;
    case 2: $approval_status = 'Rejected'; break;
    }

    /* example files */
    $example_files = '';
    $example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d ORDER BY filetype", $example_data->id);
    while ($example_files_data = db_fetch_object($example_files_q))
    {
      $file_type = '';
      switch ($example_files_data->filetype)
      {
      case 'S': $file_type = 'Main or Source'; break;
      case 'R': $file_type = 'Result'; break;
      case 'X': $file_type = 'xcos'; break;
      default:
      }
      $example_files .= l($example_files_data->filename, 'download/file/' . $example_files_data->id) . ' (' . $file_type . ')<br />';
    }

    if ($example_data->approval_status == 0)
    {
      $example_rows[] = array('data' => array($example_data->number, $example_data->caption, $approval_status, $example_files, l('Edit', 'textbook_companion/code/edit/' . $example_data->id) . ' | ' . l('Delete', 'textbook_companion/code/delete/' . $example_data->id, array('attributes' => array('onClick' => 'return confirm("Are you sure you want to delete the example?")')))), 'valign' => 'top');
    } else {
      $example_rows[] = array('data' => array($example_data->number, $example_data->caption, $approval_status, $example_files, l('Download', 'download/example/' . $example_data->id)), 'valign' => 'top');
    }
  }

  $example_header = array('Example No.', 'Caption', 'Status', 'Files', 'Action');
  $return_html .= theme_table($example_header, $example_rows);
  return $return_html;
}                                                       
