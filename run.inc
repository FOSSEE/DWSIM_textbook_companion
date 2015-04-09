<?php
// $Id$

function textbook_companion_run_form($form_state, $pref_id = NULL)
{
  $form['#redirect'] = FALSE;

  ahah_helper_register($form, $form_state);

  /* default value for ahah fields */
  if (!isset($form_state['storage']['run']['book']))
  {
    /* get the book id from url */
    $url_book_id = (int)arg(1);
    if ($url_book_id)
    {
      /* add javascript for book selected */
      $chapter_name_js = " $(document).ready(function() {
        $('#edit-run-book').val(" . $url_book_id . ");
        $('#edit-run-book').change();
      });";
      drupal_add_js($chapter_name_js, 'inline', 'footer');
    } else {
      $book_default_value = 0;
    }
  } else {
    $book_default_value =  $form_state['storage']['run']['book'];
  }

  if (!isset($form_state['storage']['run']['chapter']))
  {
    $chapter_default_value = 0;
  } else {
    if ($form_state['values']['run']['book_hidden'] !=  $form_state['values']['run']['book'])
      $chapter_default_value = 0;
    else
      $chapter_default_value = $form_state['storage']['run']['chapter'];
  }

  if (!isset($form_state['storage']['run']['example']))
  {
    $example_default_value = 0;
  } else {
    if ($form_state['values']['run']['book_hidden'] !=  $form_state['values']['run']['book'])
      $example_default_value = 0;
    else if ($form_state['values']['run']['chapter_hidden'] !=  $form_state['values']['run']['chapter'])
      $example_default_value = 0;
    else
      $example_default_value = $form_state['storage']['run']['example'];
  }

  $form['run'] = array(
    '#type' => 'fieldset',
    '#title' => t('Run Book Example'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
    '#prefix' => '<div id="run-wrapper">',
    '#suffix' => '</div>',
    '#tree' => TRUE,
  );

  $form['run']['book'] = array(
      '#type' => 'select',
      '#title' => t('Title of the Book'),
      '#options' => _list_of_books(),
      '#default_value' => $book_default_value,
      '#tree' => TRUE,
      '#ahah' => array(
        'event'   => 'change',
        'effect' => 'none',
        'path'    => ahah_helper_path(array('run')),
        'wrapper' => 'run-wrapper',
        'progress' => array(
          'type' => 'throbber',
          'message' => t(''),
        ),
      ),
  );

  /* hidden form elements */
  $form['run']['book_hidden'] = array(
    '#type'  => 'hidden',
    '#value' =>  $form_state['values']['run']['book'],
  );

  /* hidden form elements */
  $form['run']['chapter_hidden'] = array(
    '#type'  => 'hidden',
    '#value' =>  $form_state['values']['run']['chapter'],
  );

  if ($book_default_value > 0)
  {
    $book_details = _book_information($book_default_value);
    $form['run']['book_details'] = array(
      '#type' => 'item',
      '#value' => '<table cellspacing="1" cellpadding="1" border="0" style="width: 100%;" valign="top">' .
                  '<tr><td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span></td><td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span></td>' .
                  '<tr><td valign="top"><ul>' .
                  '<li><strong>Author:</strong> ' . $book_details->preference_author . '</li>' .
                  '<li><strong>Title of the Book:</strong> ' . $book_details->preference_book . '</li>' .
                  '<li><strong>Publisher:</strong> ' . $book_details->preference_publisher . '</li>' .
                  '<li><strong>Year:</strong> ' . $book_details->preference_year . '</li>' .
                  '<li><strong>Edition:</strong> ' . $book_details->preference_edition . '</li>' .
                  '</ul></td><td valign="top"><ul>' .
                  '<li><strong>Contributor Name: </strong>' . $book_details->proposal_full_name . ', ' . $book_details->proposal_course . ', ' . $book_details->proposal_branch . ', ' . $book_details->proposal_university . '</li>' .
                  '<li><strong>College Teacher: </strong>' . $book_details->proposal_faculty . '</li>' .
                  '<li><strong>Reviewer: </strong>' . $book_details->proposal_reviewer . '</li>' .
                  '</ul></td></tr>' .
                  '</table>',
    );

    $form['run']['download_book'] = array(
      '#type' => 'item',
      '#value' => l('Download', 'download/book/' . $book_default_value) . ' ' . t('(Download all the examples of the entire book)'),
    );

    /*$form['run']['download_book_pdf'] = array(
      '#type' => 'item',
      '#value' => l('Download PDF', 'textbook_companion/generate_book/' . $book_default_value) . ' ' . t('(Download PDF for all examples of the entire book)'),
    );*/

    $form['run']['chapter'] = array(
      '#type' => 'select',
      '#title' => t('Title of the Chapter'),
      '#options' => _list_of_chapters($book_default_value),
      '#default_value' => $chapter_default_value,
      '#tree' => TRUE,
      '#ahah' => array(
        'event'   => 'change',
        'effect' => 'none',
        'path'    => ahah_helper_path(array('run')),
        'wrapper' => 'run-wrapper',
        'progress' => array(
          'type' => 'throbber',
          'message' => t(''),
        ),
      ),
    );
    if ($chapter_default_value > 0)
    {
      $form['run']['download_chapter'] = array(
        '#type' => 'item',
        '#value' => l('Download', 'download/chapter/' . $chapter_default_value) . ' ' . t('(Download all the examples of the entire chapter)'),
      );
      $form['run']['example'] = array(
        '#type' => 'select',
        '#title' => t('Example No. (Caption)'),
        '#options' => _list_of_examples($chapter_default_value),
        '#default_value' => $example_default_value,
        '#tree' => TRUE,
        '#ahah' => array(
          'event'   => 'change',
          'effect' => 'none',
          'path'    => ahah_helper_path(array('run')),
          'wrapper' => 'run-wrapper',
          'progress' => array(
            'type' => 'throbber',
            'message' => t(''),
          ),
        ),
      );
    }
  }

  /* hidden form elements */
  $form['run']['update_book'] = array(
    '#type'  => 'submit',
    '#value' => t('Update'),
    '#submit' => array('ahah_helper_generic_submit'),
    '#attributes' => array('class' => 'no-js'),
  );

  $form['run']['update_chapter'] = array(
    '#type'  => 'submit',
    '#value' => t('Update'),
    '#submit' => array('ahah_helper_generic_submit'),
    '#attributes' => array('class' => 'no-js'),
  );

  /************ START OF $_POST **************/
  if ($_POST)
  {
    if (($book_default_value > 0) && ($chapter_default_value > 0) && ($example_default_value > 0))
    {
      $example_list_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $form_state['values']['run']['example']);
      if ($example_list_q)
      {
        $example_files_rows = array();
        while ($example_list_data = db_fetch_object($example_list_q))
        {
          $example_file_type = 'Source or Main file';
          $example_files_rows[] = array(l($example_list_data->filename, 'download/file/' . $example_list_data->id), $example_file_type);
        }

        /* creating list of files table */
        $example_files_header = array('Filename', 'Type');
        $example_files = theme_table($example_files_header, $example_files_rows); 
      }
      $form['run']['download_example'] = array(
        '#type' => 'item',
        '#value' => l('Download Example', 'download/example/' . $example_default_value),
      );
      $form['run']['example_files'] = array(
        '#type'  => 'item',
        '#title' => 'List of example files',
        '#value' => $example_files,
      );
      //$form['run']['submit'] = array(
      //  '#type' => 'submit',
      //  '#value' => t('Run')
      //);
      if (user_access('create feedback'))
      {
        $form['run']['feedback'] = array(
          '#type' => 'textarea',
          '#title' => t('Feedback on the above example')
        );
        $form['run']['feedback_submit'] = array(
          '#type' => 'submit',
          '#value' => t('Submit')
        );
      }
    }
  }
  /************ END OF $_POST **************/

  return $form;
}

function textbook_companion_run_form_submit($form, &$form_state)
{
  global $user;

  if ($form_state['clicked_button']['#value'] == 'Submit')
  {
    if (user_access('create feedback'))
    {
      $example_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $form_state['values']['run']['example']));
      if (!$example_data)
      {
        drupal_set_message(t('Invalid example selected'), 'error');
        return;
      }
      $chapter_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id));
      if (!$chapter_data)
      {
        drupal_set_message(t('Invalid chapter selected'), 'error');
        return;
      }
      $book_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id));
      if (!$book_data)
      {
        drupal_set_message(t('Invalid book selected'), 'error');
        return;
      }
      $proposal_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $book_data->proposal_id));
      if (!$proposal_data)
      {
        drupal_set_message(t('Invalid proposal selected'), 'error');
        return;
      }
      db_query("INSERT INTO {textbook_companion_feedback} (example_id, uid, feedback, ip_address, timestamp) VALUES (%d, %d, '%s', '%s', %d)",
          $example_data->id,
          $user->uid,
          $form_state['values']['run']['feedback'],
          $_SERVER['REMOTE_ADDR'],
          time());
  
      /* sending email */
      $param['feedback_received']['user_id'] = $user->uid;
      $param['feedback_received']['book_title'] = $book_data->book;
      $param['feedback_received']['chapter_number'] = $chapter_data->number;
      $param['feedback_received']['chapter_title'] = $chapter_data->name;
      $param['feedback_received']['example_no'] = $example_data->number;
      $param['feedback_received']['feedback'] = $form_state['values']['run']['feedback'];

      $email_to = $user->mail;// . ', ' . user_load($proposal_data->uid)->mail. ', ' . user_load($example_data->approver_uid)->mail;
      if (!drupal_mail('textbook_companion', 'feedback_received', $email_to, language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
        drupal_set_message('Error sending email message.', 'error');

      drupal_set_message(t('Thank you for your feedback.'), 'status');
    } else {
      drupal_set_message(t('You do not have permission to submit feeback.'), 'error');
    }
  }
}

function _list_of_books()
{
  $book_titles = array('0' => 'Please select...');
  $qq = "
    SELECT pre.id AS id, pre.* FROM textbook_companion_preference pre
    LEFT JOIN textbook_companion_proposal pro ON pro.id = pre.proposal_id
    WHERE pre.approval_status = 1 AND pro.proposal_status = 3 ORDER BY pre.book ASC
  ";
  $book_titles_q = db_query($qq);
  while ($book_titles_data = db_fetch_object($book_titles_q))
  {
    $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
  }
  return $book_titles;
}

function _list_of_chapters($preference_id = 0)
{
  $book_chapters = array('0' => 'Please select...');
  $book_chapters_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_id);
  while ($book_chapters_data = db_fetch_object($book_chapters_q))
  {
    $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
  }
  return $book_chapters;
}

function _list_of_examples($chapter_id = 0)
{
  $book_examples = array('0' => 'Please select...');
  $book_examples_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1 ORDER BY
    CAST(SUBSTRING_INDEX(number, '.', 1) AS BINARY) ASC,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', 2), '.', -1) AS UNSIGNED) ASC,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', -1), '.', 1) AS UNSIGNED) ASC", $chapter_id);
  while ($book_examples_data = db_fetch_object($book_examples_q))
  {
    $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
  }
  return $book_examples;
}

function _book_information($preference_id)
{
  $book_data = db_fetch_object(db_query("SELECT
    preference.book as preference_book, preference.author as preference_author, preference.isbn as preference_isbn, preference.publisher as preference_publisher, preference.edition as preference_edition, preference.year as preference_year,
    proposal.full_name as proposal_full_name, proposal.faculty as proposal_faculty, proposal.reviewer as proposal_reviewer, proposal.course as proposal_course, proposal.branch as proposal_branch, proposal.university as proposal_university
    FROM {textbook_companion_proposal} proposal LEFT JOIN {textbook_companion_preference} preference ON proposal.id = preference.proposal_id WHERE preference.id = %d", $preference_id));
  return $book_data;
}
