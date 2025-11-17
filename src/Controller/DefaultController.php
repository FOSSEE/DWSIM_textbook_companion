<?php /**
 * @file
 * Contains \Drupal\textbook_companion\Controller\DefaultController.
 */

namespace Drupal\textbook_companion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use ZipArchieve;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;



/**
 * Default controller for the textbook_companion module.
 */
class DefaultController extends ControllerBase {

  public function textbook_companion_proposal_all() {
    $user = \Drupal::currentUser();
    $page_content = "";
    if (!$user->uid) {
      drupal_set_message('It is mandatory to login on this website to access the proposal form', 'error');
      drupal_goto('');
      return;
    } //!$user->uid
	/* check if user has already submitted a proposal */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        switch ($proposal_data->proposal_status) {
          case 0:
            drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
            drupal_goto('');
            return;
            break;
          case 1:
            drupal_set_message(t('Your proposal has been approved. Please go to ' . l('Code Submission', 'textbook-companion/code') . ' to upload your code'), 'status');
            drupal_goto('');
            return;
            break;
          case 2:
            drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal below.'), 'error');
            break;
          case 3:
            drupal_set_message(t('Congratulations! You have completed your last book proposal. You can create another proposal below.'), 'status');
            break;
          default:
            drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
            drupal_goto('');
            return;
            break;
        } //$proposal_data->proposal_status
      } //$proposal_data = $proposal_q->fetchObject()
    } //$proposal_q
    $book_proposal_form = drupal_get_form("book_proposal_form");
    $page_content .= drupal_render($book_proposal_form);
    // drupal_goto("aicte_proposal");
    return $page_content;
  }

  public function textbook_companion_aicte_proposal_all() {
    $user = \Drupal::currentUser();
    $page_content = "";
    if (!$user->uid) {
      /*$query = "
		SELECT * FROM textbook_companion_aicte
		WHERE status = 0
		";
		$result = db_query($query);*/
      $query = db_select('textbook_companion_aicte');
      $query->fields('textbook_companion_aicte');
      $query->condition('status', 0);
      $result = $query->execute();
      $page_content .= "<ul>";
      $page_content .= "<li>These are the list of books available for <em>Textbook Companion</em> proposal.</li>";
      $page_content .= "<li>Please <a href='/user'><b><u>Login</u></b></a> to create a proposal.</li>";
      //$page_content .= "<li>Unable to propose particular book: <a id='aicte-report' href='#'>Click here</a></li>";
      //$page_content .= "<li>Do not wish to propose any of the below books: <a id='aicte-report' href='http://fossee.in/feedback/scilab-aicte' target = _blank>Click here</a></li>";
      $page_content .= "</ul>";
      $page_content .= "Search :  <input type='text' id='searchtext' style='width:82%'/>";
      $page_content .= "<input type='button' value ='clear' id='search_clear'/>";
      $page_content .= "<div id='aicte-list-wrapper'>";
      $num_rows = $result->rowCount();
      if ($num_rows > 0) {
        $i = 1;
        while ($row = $result->fetchObject()) {
          /* fixing title string */
          $title = "";
          $edition = "";
          $year = "";
          $title = "{$row->book} by {$row->author}";
          if ($row->edition) {
            $edition = "<i>ed</i>: {$row->edition}";
          } //$row->edition
          if ($row->year) {
            if ($row->edition) {
              $year = ", <i>pub</i>: {$row->year}";
            } //$row->edition
            else {
              $year = "<i>pub</i>: {$row->year}";
            }
          } //$row->year
          if ($edition or $year) {
            $title .= "({$edition} {$year})";
          } //$edition or $year
          $page_content .= "<div class='title'>{$i}) {$title}</div>";
          $i++;
        } //$row = $result->fetchObject()
      } //$num_rows > 0
      $page_content .= "</div>";
      /* adding aicte report form */
      //$page_content .= drupal_get_form("textbook_companion_aicte_report_form");
      return $page_content;
    } //!$user->uid
	/* check if user has already submitted a proposal */
    /* $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        switch ($proposal_data->proposal_status) {
          case 0:
            drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
            drupal_goto('');
            return;
            break;
          case 1:
            drupal_set_message(t('Your proposal has been approved. Please go to ' . l('Code Submission', 'textbook-companion/code') . ' to upload your code'), 'status');
            drupal_goto('');
            return;
            break;
          case 2:
            drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal below.'), 'error');
            break;
          case 3:
            drupal_set_message(t('Congratulations! You have completed your last book proposal. You can create another proposal below.'), 'status');
            break;
          default:
            drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
            drupal_goto('');
            return;
            break;
        } //$proposal_data->proposal_status
      } //$proposal_data = $proposal_q->fetchObject()
    } //$proposal_q
    variable_del("aicte_" . $user->uid);
    $page_content .= "<h5><b>* Please select any 3 books from the below list.</b></h5></br>";
    //$page_content .= "Unable to propose particular book: <a id='aicte-report' href='#'>Click here</a></br></br>";
    //$page_content .= "Do not wish to propose any of the below books: <a id='aicte-report' href='http://fossee.in/feedback/scilab-aicte' target = _blank>Click here</a></br></br>";
    $page_content .= "Search :  <input type='text' id='searchtext' style='width:82%'/>";
    $page_content .= "<input type='button' value ='clear' id='search_clear'/>";
    //$page_content .= drupal_get_form("textbook_companion_aicte_report_form");
    $textbook_companion_aicte_proposal_form = drupal_get_form("textbook_companion_aicte_proposal_form");
    $page_content .= drupal_render($textbook_companion_aicte_proposal_form);
    return $page_content;
  }

  public function _proposal_pending() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    /*$pending_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 ORDER BY id DESC");*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),
        l($pending_data->full_name, 'user/' . $pending_data->uid),
        date('d-m-Y', $pending_data->proposed_completion_date),
        l('Approve', 'textbook-companion/manage-proposal/approve/' . $pending_data->id) . ' | ' . l('Edit', 'textbook-companion/manage-proposal/edit/' . $pending_data->id),
      ];
    } //$pending_data = $pending_q->fetchObject()
	/* check if there are any pending proposals */
    if (!$pending_rows) {
      drupal_set_message(t('There are no pending proposals.'), 'status');
      return '';
    } //!$pending_rows
    $pending_header = [
      'Date of Submission',
      'Contributor Name',
      'Proposed Date of Completion',
      'Action',
    ];
    $output = theme('table', [
      'header' => $pending_header,
      'rows' => $pending_rows,
    ]);
    return $output;
  }

  public function _proposal_all() {
    function _tbc_ext($status, $preference_id) {
      if ($status == "Approved") {
        //return " | " . l("ER", "tbc_external_review/add_book/" . $preference_id);
        return "";
      } //$status == "Approved"
      else {
        return "";
      }
    }
    /* get pending proposals to be approved */
    $proposal_rows = [];
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} ORDER BY id DESC");*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      /* get preference */
      /*$preference_q = db_query("SELECT * FROM textbook_companion_preference WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);   
		$preference_data = db_fetch_object($preference_q);*/
      $query = db_select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('proposal_id', $proposal_data->id);
      $query->condition('approval_status', 1);
      $query->range(0, 1);
      $preference_q = $query->execute();
      $preference_data = $preference_q->fetchObject();
      if (!$preference_data) {
        /* $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND pref_number = 1 LIMIT 1", $proposal_data->id);  
			$preference_data = db_fetch_object($preference_q);*/
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('proposal_id', $proposal_data->id);
        $query->condition('pref_number', 1);
        //$query->condition('approval_status', 0);        
        $query->range(0, 1);
        $preference_q = $query->execute();
        $preference_data = $preference_q->fetchObject();
      } //!$preference_data
      $proposal_status = '';
      switch ($proposal_data->proposal_status) {
        case 0:
          $proposal_status = 'Pending';
          break;
        case 1:
          $proposal_status = 'Approved';
          break;
        case 2:
          $proposal_status = 'Dis-approved';
          break;
        case 3:
          $proposal_status = 'Completed';
          break;
        case 4:
          $proposal_status = 'External';
          break;
        case 5:
          $proposal_status = 'Submitted all codes';
          break;
        default:
          $proposal_status = 'Unknown';
          break;
      } //$proposal_data->proposal_status
      if ($proposal_data->proposed_completion_date != 0) {
        $proposed_completion_date = date('d-m-Y', $proposal_data->proposed_completion_date);
      } //$proposal_data->proposed_completion_date != 0
      else {
        $proposed_completion_date = "-----";
      }
      $proposal_rows[] = [
        date('d-m-Y', $proposal_data->creation_date),
        "{$preference_data->book} <br> 
<em>by {$preference_data->author}</em>",
        l($proposal_data->full_name, 'user/' . $proposal_data->uid),
        date('d-m-Y', $proposal_data->completion_date),
        $proposed_completion_date,
        $proposal_status,
        l('Status', 'textbook-companion/manage-proposal/status/' . $proposal_data->id) . ' | ' . l('Edit', 'textbook-companion/manage-proposal/edit/' . $proposal_data->id) . _tbc_ext($proposal_status, $preference_data->id),
      ];
    } //$proposal_data = $proposal_q->fetchObject()
	/* check if there are any pending proposals */
    if (!$proposal_rows) {
      drupal_set_message(t('There are no proposals.'), 'status');
      return '';
    } //!$proposal_rows
    $proposal_header = [
      'Date of Submission',
      'Title of the Book',
      'Contributor Name',
      'Actual Date of Completion',
      'Proposed Date of Completion',
      'Status',
      'Action',
    ];
    $output = theme('table', [
      'header' => $proposal_header,
      'rows' => $proposal_rows,
    ]);
    return $output;
  }

  public function _failed_all($preference_id = 0, $confirm = "") {
    $page_content = "";
    if ($preference_id && $confirm == "yes") {
      /*$query = "
		SELECT *, pro.id as proposal_id FROM textbook_companion_proposal pro
		LEFT JOIN textbook_companion_preference pre ON pre.proposal_id = pro.id
		LEFT JOIN users usr ON usr.uid = pro.uid
		WHERE pre.id = {$preference_id}
		";
		$result = db_query($query);
		$row = db_fetch_object($result);*/
      $query = db_select('textbook_companion_proposal', 'pro');
      $query->fields('*', ['']);
      $query->fields('pro', ['id']);
      $query->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
      $query->leftJoin('users', 'usr', 'usr.uid = pro.uid');
      $query->condition('pre.id', '$preference_id');
      $result = $query->execute();
      $row = $result->fetchObject();
      /* increment failed_reminder */
      /*$query = "
		UPDATE textbook_companion_proposal
		SET failed_reminder = failed_reminder + 1
		WHERE id = {$row->proposal_id}
		";
		db_query($query);*/
      $query = db_update('textbook_companion_proposal');
      $query->fields(['failed_reminder' => 'failed_reminder + 1']);
      $query->condition('id', '$row->proposal_id');
      $num_updated = $query->execute();
      /* sending mail */
      $to = $row->mail;
      $subject = "Failed to upload the TBC codes on time";
      $body = "
    <p>
      Dear {$row->name},<br><br>
      This is to inform you that you have failed to upload the TBC codes on time.<br>
      Please note that the time you have taken is way past the deadline as well.<br>
      Kindly upload the TBC codes on the interface within 5 days from now.<br>
      Failure to submit the same will result in disapproval of your work and cancellation of your internship.<br><br>
      Regards,<br>
      DWSIM TBC Team,<br>
      FOSSEE.
    </p>
    ";
      $message = [
        "to" => $to,
        "subject" => $subject,
        "body" => $body,
        "headers" => [
          "From" => "contact-dwsim@fossee.in",
          "Bcc" => "contact-dwsim@fossee.in",
          "Content-Type" => "text/html; charset=UTF-8; format=flowed",
        ],
      ];
      drupal_mail_send($message);
      drupal_set_message("Reminder sent successfully.");
      drupal_goto("textbook-companion/manage-proposal/failed");
    } //$preference_id && $confirm == "yes"
    else {
      if ($preference_id) {
        /*$query = "
		SELECT * FROM textbook_companion_preference pre
		LEFT JOIN textbook_companion_proposal pro ON pro.id = pre.proposal_id
		WHERE pre.id = {$preference_id}
		";
		$result = db_query($query);
		$row = db_fetch_object($result);*/
        $query = db_select('textbook_companion_preference', 'pre');
        $query->fields('pre');
        $query->leftJoin('textbook_companion_proposal', 'pro', 'pro.id = pre.proposal_id');
        $query->condition('pre.id', $preference_id);
        $result = $query->execute();
        $row = $result->fetchObject();
        $page_content .= "Are you sure you want to notify?<br><br>";
        $page_content .= "Book: <b>{$row->book}</b><br>";
        $page_content .= "Author: <b>{$row->author}</b><br>";
        $page_content .= "Contributor: <b>{$row->full_name}</b><br>";
        $page_content .= "Expected Completion Date: <b>" . date("d-m-Y", $row->completion_date) . "</b><br><br>";
        $page_content .= l("Yes", "textbook-companion/manage-proposal/failed/{$preference_id}/yes") . " | ";
        $page_content .= l("Cancel", "textbook-companion/manage-proposal/failed");
      } //$preference_id
      else {
        /*$query = "
		SELECT * FROM textbook_companion_proposal pro
		LEFT JOIN textbook_companion_preference pre ON pre.proposal_id = pro.id
		LEFT JOIN users usr ON usr.uid = pro.uid
		WHERE pro.proposal_status = 1 AND pre.approval_status = 1 AND pro.completion_date < %d
		ORDER BY failed_reminder
		";
		$result = db_query($query, time());*/
        $query = db_select('textbook_companion_proposal', 'pro');
        $query->fields('pro');
        $query->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
        $query->leftJoin('users', 'usr', 'usr.uid = pro.uid');
        $query->condition('pro.proposal_status', 1);
        $query->condition('pre.approval_status', 1);
        $query->condition('pro.completion_date', '%time()', '<');
        $query->orderBy('failed_reminder', 'ASC');
        $result = $query->execute();
        $headers = [
          "Date of Submission",
          "Book",
          "Contributor Name",
          "Expected Completion Date",
          "Remainders",
          "Action",
        ];
        $rows = [];
        while ($row = $result->fetchObject()) {
          $item = [
            date("d-m-Y", $row->creation_date),
            "{$row->book}<br><i>by</i> {$row->author}",
            $row->name,
            date("d-m-Y", $row->completion_date),
            $row->failed_reminder,
            l("Remind", "textbook-companion/manage-proposal/failed/{$row->id}"),
          ];
          array_push($rows, $item);
        } //$row = $result->fetchObject()
        $page_content .= theme('table', [
          'header' => $headers,
          'rows' => $rows,
        ]);
      }
    }
    return $page_content;
  }

  public function code_approval() {
    /* get a list of unapproved chapters */
    $query = db_select('textbook_companion_example', 'e');
    $query->fields('c', [
      'id',
      'number',
      'name',
      'preference_id',
    ]);
    $query->addField('c', 'id', 'c_id');
    $query->addField('c', 'number', 'c_number');
    $query->addField('c', 'name', 'c_name');
    $query->addField('c', 'preference_id', 'c_preference_id');
    $query->innerJoin('textbook_companion_chapter', 'c', 'c.id = e.chapter_id');
    $query->condition('e.approval_status', 0);
    $pending_chapter_q = $query->execute();
    if (!$pending_chapter_q) {
      drupal_set_message(t('There are no pending code approvals.'), 'status');
      return '';
    } //!$pending_chapter_q
    $rows = [];
    while ($row = $pending_chapter_q->fetchObject()) {
      /* get preference data */
      $query = db_select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('id', $row->c_preference_id);
      $result = $query->execute();
      $preference_data = $result->fetchObject();
      /* get proposal data */
      $query = db_select('textbook_companion_proposal');
      $query->fields('textbook_companion_proposal');
      $query->condition('id', $preference_data->proposal_id);
      $result = $query->execute();
      $proposal_data = $result->fetchObject();
      /* setting table row information */
      $rows[] = [
        $preference_data->book,
        $row->c_number,
        $row->c_name,
        $proposal_data->full_name,
        l('Edit', 'textbook-companion/code-approval/approve/' . $row->c_id),
      ];
    } //$row = $pending_chapter_q->fetchObject()
	/* check if there are any pending proposals */
    if (!$rows) {
      drupal_set_message(t('There are no pending proposals'), 'status');
      return '';
    } //!$rows
    $header = [
      'Title of the Book',
      'Chapter Number',
      'Title of the Chapter',
      'Contributor Name',
      'Actions',
    ];
    $output = theme('table', [
      'header' => $header,
      'rows' => $rows,
    ]);
    return $output;
  }

  public function list_chapters() {
    $user = \Drupal::currentUser();
    /************************ start approve book details ************************/
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
	$proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      drupal_set_message("Please submit a " . l('proposal', 'textbook-companion/proposal') . ".", 'error');
      drupal_goto('');
    } //!$proposal_data
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
          drupal_goto('');
          return;
          break;
        case 2:
          drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'error');
          drupal_goto('');
          return;
          break;
        case 3:
          drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
          drupal_goto('');
          return;
          break;
        default:
          drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
          drupal_goto('');
          return;
          break;
      } //$proposal_data->proposal_status
    } //$proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4


    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
	$preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if ($preference_data->submited_all_examples_code == 1) {
      drupal_set_message(t('You have already submited your all codes for this book to review, hence you can not upload more code, for any query please write us.'), 'status');
      drupal_goto('');
      return;
    } //$preference_data->submited_all_examples_code == 1
    if (!$preference_data) {
      drupal_set_message(t('Invalid Book Preference status. Please contact site administrator for further information.'), 'error');
      drupal_goto('');
      return;
    } //!$preference_data
	/************************ end approve book details **************************/
    $return_html = '<br />';
    $return_html .= '<strong>Title of the Book:</strong><br />' . $preference_data->book . '<br /><br />';
    $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->full_name . '<br /><br />';
    $return_html .= l('Upload Example Code', 'textbook-companion/code/upload') . '<br />';
    /* get chapter list */
    $chapter_rows = [];
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_data->id);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_data->id);
    $query->orderBy('number', 'ASC');
    $chapter_q = $query->execute();
    while ($chapter_data = $chapter_q->fetchObject()) {
      /* get example list */
      /* $example_q = db_query("SELECT count(*) as example_count FROM {textbook_companion_example} WHERE chapter_id = %d", $chapter_data->id);
		$example_data = db_fetch_object($example_q);*/
      $query = db_select('textbook_companion_example');
      $query->addExpression('count(*)', 'example_count');
      $query->condition('chapter_id', $chapter_data->id);
      $result = $query->execute();
      $example_data = $result->fetchObject();
      $chapter_rows[] = [
        $chapter_data->number,
        $chapter_data->name . ' (' . l('Edit', 'textbook-companion/code/chapter/edit/' . $chapter_data->id) . ')',
        $example_data->example_count,
        l('View', 'textbook-companion/code/list-examples/' . $chapter_data->id),
      ];
    } //$chapter_data = $chapter_q->fetchObject()
	/* check if there are any chapters */
    if (!$chapter_rows) {
      drupal_set_message(t('No uploads found.'), 'status');
      return $return_html;
    } //!$chapter_rows
    $chapter_header = [
      'Chapter No.',
      'Title of the Chapter',
      'Uploaded Examples',
      'Actions',
    ];
    $return_html .= theme('table', [
      'header' => $chapter_header,
      'rows' => $chapter_rows,
    ]);
    $submited_all_example = drupal_get_form("all_example_submitted_check_form", $preference_data->id);
    $return_html .= drupal_render($submited_all_example);
    return $return_html;
  }

  public function upload_examples() {
    return drupal_get_form('upload_examples_form');
  }


  public function _upload_examples_delete() {
    $user = \Drupal::currentUser();
    $root_path = textbook_companion_path();
    $example_id = arg(3);
    //var_dump($example_id);die;
	/* check example */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
	$example_data = db_fetch_object($example_q);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_data = $result->fetchObject();
    if (!$example_data) {
      drupal_set_message('Invalid example.', 'error');
      drupal_goto('textbook-companion/code');
      return;
    } //!$example_data
    if ($example_data->approval_status != 0) {
      drupal_set_message('You cannnot delete an example after it has been approved. Please contact site administrator if you want to delete this example.', 'error');
      drupal_goto('textbook-companion/code');
      return;
    } //$example_data->approval_status != 0
	/*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d LIMIT 1", $example_data->chapter_id);
	$chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $query->range(0, 1);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      drupal_set_message('You do not have permission to delete this example.', 'error');
      drupal_goto('textbook-companion/code');
      return;
    } //!$chapter_data
	/*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d LIMIT 1", $chapter_data->preference_id);
	$preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      drupal_set_message('You do not have permission to delete this example.', 'error');
      drupal_goto('textbook-companion/code');
      return;
    } //!$preference_data
	/*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d AND uid = %d LIMIT 1", $preference_data->proposal_id, $user->uid);
	$proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $query->condition('uid', $user->uid);
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      drupal_set_message('You do not have permission to delete this example.', 'error');
      drupal_goto('textbook-companion/code');
      return;
    } //!$proposal_data
	/* deleting example files */
    if (delete_example($example_data->id)) {
      drupal_set_message('Example deleted.', 'status');
      /* sending email */
      $email_to = $user->mail;
      $from = variable_get('textbook_companion_from_email', '');
      $bcc = variable_get('textbook_companion_emails', '');
      $cc = variable_get('textbook_companion_cc_emails', '');
      $params['example_deleted_user']['book_title'] = $preference_data->book;
      $params['example_deleted_user']['chapter_title'] = $chapter_data->name;
      $params['example_deleted_user']['example_number'] = $example_data->number;
      $params['example_deleted_user']['example_caption'] = $example_data->caption;
      $params['example_deleted_user']['user_id'] = $user->uid;
      $params['example_deleted_user']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      if (!drupal_mail('textbook_companion', 'example_deleted_user', $email_to, language_default(), $params, $from, TRUE)) {
        drupal_set_message('Error sending email message.', 'error');
      }
    } //delete_example($example_data->id)
    else {
      drupal_set_message('Error deleting example.', 'status');
    }
    drupal_goto('textbook-companion/code');
    return;
  }

  public function list_examples() {
    $user = \Drupal::currentUser();
    /************************ start approve book details ************************/
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
	$proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      drupal_set_message("Please submit a " . l('proposal', 'textbook-companion/proposal') . ".", 'error');
      drupal_goto('');
    } //!$proposal_data
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
          drupal_goto('');
          return;
          break;
        case 2:
          drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'error');
          drupal_goto('');
          return;
          break;
        case 3:
          drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
          drupal_goto('');
          return;
          break;
        default:
          drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
          drupal_goto('');
          return;
          break;
      } //$proposal_data->proposal_status
    } //$proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4
	/*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
	$preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      drupal_set_message(t('Invalid Book Preference status. Please contact site administrator for further information.'), 'error');
      drupal_goto('');
      return;
    } //!$preference_data
	/************************ end approve book details **************************/
    /* get chapter details */
    $chapter_id = arg(3);
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d LIMIT 1", $chapter_id, $preference_data->id);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $query->range(0, 1);
    $chapter_q = $query->execute();
    if ($chapter_data = $chapter_q->fetchObject()) {
      $return_html = '<br />';
      $return_html .= '<strong>Title of the Book:</strong><br />' . $preference_data->book . '<br /><br />';
      $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->full_name . '<br /><br />';
      $return_html .= '<strong>Chapter Number:</strong><br />' . $chapter_data->number . '<br /><br />';
      $return_html .= '<strong>Title of the Chapter:</strong><br />' . $chapter_data->name . '<br />';
    } //$chapter_data = $chapter_q->fetchObject()
    else {
      drupal_set_message(t('Invalid chapter.'), 'error');
      drupal_goto('textbook-companion/code');
      return;
    }
    $return_html .= '<br />' . l('Back to Chapter List', 'textbook-companion/code');
    /* get example list */
    $example_rows = [];
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $example_q = $query->execute();
    while ($example_data = $example_q->fetchObject()) {
      /* approval status */
      $approval_status = '';
      switch ($example_data->approval_status) {
        case 0:
          $approval_status = 'Pending';
          break;
        case 1:
          $approval_status = 'Approved';
          break;
        case 2:
          $approval_status = 'Rejected';
          break;
      } //$example_data->approval_status
		/* example files */
      $example_files = '';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d ORDER BY filetype", $example_data->id);*/
      $query = db_select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('example_id', $example_data->id);
      $query->orderBy('filetype', 'ASC');
      $example_files_q = $query->execute();
      while ($example_files_data = $example_files_q->fetchObject()) {
        $file_type = '';
        switch ($example_files_data->filetype) {
          case 'S':
            $file_type = 'Main or Source';
            break;
          case 'R':
            $file_type = 'Result';
            break;
          case 'X':
            $file_type = 'xcos';
            break;
          default:
        } //$example_files_data->filetype
        $example_files .= l($example_files_data->filename, 'textbook-companion/download/file/' . $example_files_data->id) . ' (' . $file_type . ')<br />';
      } //$example_files_data = $example_files_q->fetchObject()
      if ($example_data->approval_status == 0) {
        $example_rows[] = [
          'data' => [
            $example_data->number,
            $example_data->caption,
            $approval_status,
            $example_files,
            l('Edit', 'textbook-companion/code/edit/' . $example_data->id) . ' | ' . l('Delete', 'textbook-companion/code/delete/' . $example_data->id, [
              'attributes' => [
                'onClick' => 'return confirm("Are you sure you want to delete the example?")'
                ]
              ]),
          ],
          'valign' => 'top',
        ];
      } //$example_data->approval_status == 0
      else {
        $example_rows[] = [
          'data' => [
            $example_data->number,
            $example_data->caption,
            $approval_status,
            $example_files,
            l('Download', 'textbook-companion/download/example/' . $example_data->id),
          ],
          'valign' => 'top',
        ];
      }
    } //$example_data = $example_q->fetchObject()
    $example_header = [
      'Example No.',
      'Caption',
      'Status',
      'Files',
      'Action',
    ];
    $return_html .= theme('table', [
      'header' => $example_header,
      'rows' => $example_rows,
    ]);

    return $return_html;
  }

  public function textbook_companion_browse_book() {
    $return_html = _browse_list('book');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the title of the book";
      return $return_html;
    }
    $book_rows = [];
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE book like '%s%%' AND approval_status = 1", $query_character);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('book', '' . $query_character . '%%', 'like');
    $query->condition('approval_status', 1);
    $book_q = $query->execute();
    while ($book_data = $book_q->fetchObject()) {
      $book_rows[] = [
        l($book_data->book, 'textbook_run/' . $book_data->id),
        $book_data->author,
      ];
    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that title";
    }
    else {
      $book_header = [
        'Title of the Book',
        'Author Name',
      ];
      $return_html .= theme('table', [
        'headers' => $book_header,
        'rows' => $book_rows,
      ]);
    }
    return $return_html;
  }

  public function textbook_companion_browse_author() {
    $return_html = _browse_list('author');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the author's name";
      return $return_html;
    }
    $book_rows = [];
    /*$book_q = db_query("SELECT pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as id FROM {textbook_companion_preference} pe RIGHT JOIN  {textbook_companion_proposal} po on pe.proposal_id=po.id  WHERE po.proposal_status=3 and pe.approval_status = 1", $query_character);*/
    $query = db_select('textbook_companion_preference', 'pe');
    $query->fields('pe', [
      'book',
      'author',
      'publisher',
      'year',
      'id',
    ]);
    $query->rightJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $book_q = $query->execute();
    while ($book_data = $book_q->fetchObject()) {
      /* Initial's fix algorithm */
      preg_match_all("/{$query_character}[a-z]+/", $book_data->author, $matches);
      if (count($matches) > 0) {
        /* Remove the word "And"/i from the match array and make match bold */
        if (count($matches[0]) > 0) {
          foreach ($matches[0] as $key => $value) {
            if (strtolower($value) == "and") {
              unset($matches[$key]);
            }
            else {
              $matches[0][$key] = "<b>" . $value . "</b>";
              $book_data->author = str_replace($value, $matches[0][$key], $book_data->author);
            }
          }
        }
        /* Check count of matches after removing And */
        if (count($matches[0]) > 0) {
          $book_rows[] = [
            l($book_data->book, 'textbook_run/' . $book_data->id),
            $book_data->author,
          ];
        }
      }
    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that author's name";
    }
    else {
      $book_header = [
        'Title of the Book',
        'Author Name',
      ];
      $return_html .= theme('table', [
        'headers' => $book_header,
        'rows' => $book_rows,
      ]);
    }
    return $return_html;
  }

  public function textbook_companion_browse_student() {
    $return_html = _browse_list('student');
    $return_html .= '<br /><br />';
    $query_character = arg(2);
    //print $query_character;
    //die();
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the student's name";
      return $return_html;
    }
    $book_rows = [];
    /*$student_q = db_query("
    SELECT po.full_name, pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as pe_id, po.approval_date as approval_date
    FROM textbook_companion_preference pe LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id 
    WHERE po.proposal_status = 3 AND pe.approval_status = 1 AND full_name LIKE '%s%%'
    ", $query_character);*/
    $query = db_select('textbook_companion_preference', 'pe');
    $query->fields('po', [
      'full_name',
      'approval_date',
    ]);
    $query->fields('pe', [
      'book',
      'author',
      'publisher',
      'year',
      'id',
    ]);
    $query->leftJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $query->condition('full_name', '' . $query_character . '%%', 'LIKE');
    $student_q = $query->execute();
    while ($student_data = $student_q->fetchObject()) {
      $book_rows[] = [
        l($student_data->book, 'textbook_run/' . $student_data->pe_id),
        $student_data->full_name,
      ];
    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that student's name";
    }
    else {
      $book_header = [
        'Title of the Book',
        'Student Name',
      ];
      $return_html .= theme('table', [
        'headers' => $book_header,
        'rows' => $book_rows,
      ]);
    }
    return $return_html;
  }

  // public function textbook_companion_download_example_file() {
  //   $example_file_id = arg(3);
  //   $root_path = textbook_companion_path();
  //   $root_temp_path = textbook_companion_temp_path();
  //   /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE id = %d LIMIT 1", $example_file_id);
	// $example_file_data = db_fetch_object($example_files_q);*/
  //   /*$query = db_select('textbook_companion_example_files');
	// $query->fields('textbook_companion_example_files');
	// $query->condition('id', $example_file_id);
	// $query->range(0, 1);
	// $result = $query->execute();*/
  //   $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.id= :example_id LIMIT 1", [
  //     ':example_id' => $example_file_id
  //     ]);
  //   $example_file_data = $example_files_q->fetchObject();
  //   header('Content-Type: ' . $example_file_data->filemime);
  //   header('Content-disposition: attachment; filename="' . $example_file_data->filename . '"');
  //   header('Content-Length: ' . filesize($root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath));
  //   ob_clean();
  //   readfile($root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath);
  // }
  public function textbook_companion_download_example_file($example_file_id) {
  // Get the example file ID from the route.
  $example_file_id = \Drupal::routeMatch()->getParameter('example_file_id');
  
  if (empty($example_file_id)) {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
  }

  // Get root path (assuming this function exists in your module).
  $root_path =\Drupal::service("textbook_companion_global")->textbook_companion_path();

  // Database query using Drupal 10 Database API.
  // $connection = \Drupal::database();
  // $query = $connection->select('textbook_companion_preference', 'tcp')
  //   ->join('textbook_companion_chapter', 'tcc', 'tcp.id = tcc.preference_id')
  //   ->join('textbook_companion_example', 'tce', 'tcc.id = tce.chapter_id')
  //   ->join('textbook_companion_example_files', 'tcef', 'tce.id = tcef.example_id')
  //       ->fields('tcef')

  //   ->condition('tcef.id', $example_file_id)
  //   ->range(0, 1);

  $connection = \Drupal::database();

$query = $connection->select('textbook_companion_preference', 'tcp');
$query->join('textbook_companion_chapter', 'tcc', 'tcp.id = tcc.preference_id');
$query->join('textbook_companion_example', 'tce', 'tcc.id = tce.chapter_id');
$query->join('textbook_companion_example_files', 'tcef', 'tce.id = tcef.example_id');

// Fetch both file info and directory name
$query->fields('tcef');
$query->addField('tcp', 'directory_name', 'directory_name');

$query->condition('tcef.id', $example_file_id);
$query->range(0, 1);


// Execute.

  $example_file_data = $query->execute()->fetchObject();

  if (!$example_file_data) {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
  }

  // Build full file path.
  $file_path = $root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath;

  // var_dump( $example_file_data->directory_name);die;
  if (!file_exists($file_path)) {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('File not found.');
  }

  // Prepare response using Symfony's BinaryFileResponse.
  $response = new BinaryFileResponse($file_path);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    $example_file_data->filename
  );
  $response->headers->set('Content-Type', $example_file_data->filemime);
  $response->headers->set('Content-Length', filesize($file_path));

  return $response;
}



  public function textbook_companion_download_sample_code() {
    $proposal_id = arg(3);
    $root_path = textbook_companion_samplecode_path();
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_file_data = $result->fetchObject();
    $samplecodename = substr($example_file_data->samplefilepath, strrpos($example_file_data->samplefilepath, '/') + 1);
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename="' . $samplecodename . '"');
    header('Content-Length: ' . filesize($root_path . $example_file_data->samplefilepath));
    ob_clean();
    readfile($root_path . $example_file_data->samplefilepath);
  }

  // public function textbook_companion_download_example() {
  //   $example_id = arg(3);
  //   $root_path = textbook_companion_path();
  //   $root_temp_path = textbook_companion_temp_path();
  //   /* get example data */
  //   /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $example_id);
	// $example_data = db_fetch_object($example_q);*/
  //   $query = db_select('textbook_companion_example');
  //   $query->fields('textbook_companion_example');
  //   $query->condition('id', $example_id);
  //   $result = $query->execute();
  //   $example_data = $result->fetchObject();
  //   /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
	// $chapter_data = db_fetch_object($chapter_q);*/
  //   $query = db_select('textbook_companion_chapter');
  //   $query->fields('textbook_companion_chapter');
  //   $query->condition('id', $example_data->chapter_id);
  //   $result = $query->execute();
  //   $chapter_data = $result->fetchObject();
  //   /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
  //   /* $query = db_select('textbook_companion_example_files');
	// $query->fields('textbook_companion_example_files');
	// $query->condition('example_id', $example_id);
	// $example_files_q = $query->execute();*/
  //   $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
  //     ':example_id' => $example_id
  //     ]);
  //   $EX_PATH = 'EX' . $example_data->number . '/';
  //   /* zip filename */
  //   if (!is_dir($root_temp_path . 'tbc_download_temp')) {
  //     mkdir($root_temp_path . 'tbc_download_temp');
  //   }
  //   $zip_filename = $root_temp_path . 'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
  //   /* creating zip archive on the server */
  //   $zip = new ZipArchive();
  //   $zip->open($zip_filename, ZipArchive::CREATE);
  //   while ($example_files_row = $example_files_q->fetchObject()) {
  //     $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $EX_PATH . $example_files_row->filename);
  //   } //$example_files_row = $example_files_q->fetchObject()
  //   $zip_file_count = $zip->numFiles;
  //   $zip->close();
  //   if ($zip_file_count > 0) {
  //     /* download zip file */
  //     header('Content-Type: application/octet-stream');
  //     header('Content-disposition: attachment; filename="EX' . $example_data->number . '.zip"');
  //     header('Content-Length: ' . filesize($zip_filename));
  //     ob_clean();
  //     readfile($zip_filename);
  //     unlink($zip_filename);
  //   } //$zip_file_count > 0
  //   else {
  //     drupal_set_message("There are no files in this examples to download", 'error');
  //     drupal_goto('textbook-companion/textbook-run');
  //   }
  // }

//   public function textbook_companion_download_chapter() {
//     // $chapter_id = arg(3);
//             $route_match = \Drupal::routeMatch();

// $chapter_id = (int) $route_match->getParameter('chapter_id');

//     $root_path = \Drupal::service("textbook_companion_global")->textbook_companion_path();
//     // var_dump($chapter_id);die;
//     // var_dump($root_path);die;

// // $connection = \Drupal::service('database');
//  $connection = \Drupal::database();
//     $query = \Drupal::database()->select('textbook_companion_preference');
//     $query->fields('textbook_companion_preference');
//     $query->condition('id', $book_id);
//     $result = $query->execute();
//     $book_data = $result->fetchObject();
//     // Load chapter.
//     $query = $connection->select('textbook_companion_chapter', 'tcc')
//       ->fields('tcc')
//       ->condition('number', $chapter_id);
//     $chapter_data = $query->execute()->fetchObject();
//      $chapter_data = db_fetch_object($chapter_q);
    
//    var_dump($chpater_data);die;

//     // if (!$chapter_data) {
//     //   \Drupal::messenger()->addMessage("Invalid chapter.", 'error');
//     //   // return new RedirectResponse('/textbook-companion/textbook-run');
//     //         return $this->redirect('textbook_companion.run_form', ['book_pref_id' => $book_id]);

//     // }

//     // $CH_PATH = 'CH' . $chapter_data->number . '/';
//     $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

//     $zip = new \ZipArchive();
//     $zip->open($zip_filename, \ZipArchive::CREATE);

//     // Fetch approved examples.
//     $query = \Drupal::database()->select('textbook_companion_example');
//     $query->fields('textbook_companion_example');
//     $query->condition('chapter_id', $chapter_id);
//     $query->condition('approval_status', 1);
//     $example_q = $query->execute();
//     // $example_q_data = $example_q->fetchObject();
//     foreach ($example_q as $example_row) {
//       $EX_PATH = 'EX' . $example_row->number . '/';

//       // Fetch files for this example.
//       $example_files_q = $connection->query(
//         "SELECT tcef.* , tcp.directory_name
//          FROM {textbook_companion_preference} tcp
//          JOIN {textbook_companion_chapter} tcc ON tcp.id = tcc.preference_id 
//          JOIN {textbook_companion_example} tce ON tcc.id = tce.chapter_id 
//          JOIN {textbook_companion_example_files} tcef ON tce.id = tcef.example_id 
//          WHERE tcef.example_id = :example_id",
//         [':example_id' => $example_row->id]
//       );
  
//  $example_files_q_data =   $example_files_q->fetchAll();
//       foreach ($example_files_q_data as $example_files_row) {
//         $zip->addFile(
//           $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath,
//           $CH_PATH . $EX_PATH . $example_files_row->filename
//         );
//       }
//       // while ($example_files_row = $example_files_q->fetchObject())
//       //     {
//       //       $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $CH_PATH . $EX_PATH . $example_files_row->filename);
//       //     }
//       }
//         // var_dump($example_file_q);die;
// // var_dump($example_file_q);die;
//     $zip_file_count = $zip->numFiles;
//     $zip->close();



//        if ($zip_file_count > 0)
//       {
//         /* download zip file */
//         header('Content-Type: application/zip');
//         header('Content-disposition: attachment; filename="CH' . $chapter_data->number . '.zip"');
//         header('Content-Length: ' . filesize($zip_filename));
//         ob_clean();
//         readfile($zip_filename);
//         unlink($zip_filename);
//       }
   
      
    
//     else {

 

// // Show error message
// $this->messenger()->addMessage("There are no examples in this chapter to download", 'error');

// // Redirect to a route
// $url = Url::fromRoute('textbook_companion.run_form'); // Use your route name
// return new RedirectResponse($url->toString());

//       // \Drupal::messenger()->addMessage("There are no examples in this chapter to download", 'error');
//       // return new RedirectResponse('/textbook-companion/textbook-run');
//       // return $this->redirect('textbook_companion.run_form', ['book_pref_id' => $book_id]);
// // $url = Url::fromRoute('textbook_companion.run_form');
// // return new RedirectResponse($url->toString());
// // return \Drupal::messenger()->addMessage("There are no examples in this chapter to download", 'error');;
    
//   }



//   }

public function textbook_companion_download_chapter() {

  // Get chapter id from route
  $route_match = \Drupal::routeMatch();
  $chapter_id = (int) $route_match->getParameter('chapter_id');

  // Get root path (ensure trailing slash)
  $root_path = \Drupal::service("textbook_companion_global")->textbook_companion_path();
  $root_path = rtrim($root_path, '/') . '/';

  // Fetch chapter data
  $query = \Drupal::database()->select('textbook_companion_chapter', 'tcc');
  $query->fields('tcc');
  $query->condition('id', $chapter_id);
  $chapter_data = $query->execute()->fetchObject();

  if (!$chapter_data) {
    \Drupal::messenger()->addError("Invalid chapter.");
    return $this->redirect('textbook_companion.run_form');
  }

  $CH_PATH = 'CH' . $chapter_data->number . '/';

  // Create temporary zip location
  $temp_dir = $root_path . 'tbc_download_temp/';
  if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, TRUE);
  }
  $zip_filename = $temp_dir . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

  // Create zip
  $zip = new \ZipArchive();
  if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
    \Drupal::messenger()->addError("Unable to create zip file.");
    return $this->redirect('textbook_companion.run_form');
  }

  // Fetch approved examples
  $db = \Drupal::database();
  $example_q = $db->select('textbook_companion_example', 'tce')
    ->fields('tce')
    ->condition('chapter_id', $chapter_id)
    ->condition('approval_status', 1)
    ->execute();

  while ($example_row = $example_q->fetchObject()) {
    $EX_PATH = 'EX' . $example_row->number . '/';

    // Get example files with joined preference directory
    $example_files_q = $db->query("
        SELECT tcef.filename, tcef.filepath, tcp.directory_name
        FROM textbook_companion_preference tcp
        JOIN textbook_companion_chapter tcc ON tcp.id = tcc.preference_id
        JOIN textbook_companion_example tce ON tcc.id = tce.chapter_id
        JOIN textbook_companion_example_files tcef ON tce.id = tcef.example_id
        WHERE tcef.example_id = :example_id",
      [':example_id' => $example_row->id]
    );

    while ($example_files_row = $example_files_q->fetchObject()) {

      $file_full_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;

      if (file_exists($file_full_path)) {
        $zip->addFile($file_full_path, $CH_PATH . $EX_PATH . $example_files_row->filename);
      }
      else {
        \Drupal::logger('textbook_companion')->warning('Missing file: ' . $file_full_path);
      }
    }
  }

  $zip_file_count = $zip->numFiles;
  $zip->close();

  if ($zip_file_count < 1) {
    unlink($zip_filename);
    \Drupal::messenger()->addError("There are no examples in this chapter to download.");
    return $this->redirect('textbook_companion.run_form');
  }

  // Return as downloadable response
  $response = new BinaryFileResponse($zip_filename);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    'CH' . $chapter_data->number . '.zip'
  );

  // Delete after sending to user
  $response->deleteFileAfterSend(true);

  return $response;
}


  
public function textbook_companion_download_book() {
    // $book_id = arg(3);
        $route_match = \Drupal::routeMatch();

$book_id = (int) $route_match->getParameter('book_id');
    /* get solution data */
    $root_path = \Drupal::service("textbook_companion_global")->textbook_companion_path();
    /* get example data */
    // var_dump($root_path);die;
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
    $book_data = db_fetch_object($book_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $book_id);
    $result = $query->execute();
    $book_data = $result->fetchObject();
    $zipname = str_replace(' ', '_', ($book_data->book));
    $directory_name = $book_data->directory_name;
    $BK_PATH = $zipname . '/';

    // var_dump($book_data);die;
    /* zip filename */
         $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

        //  var_Dump($zip_filename);die;
    /* creating zip archive on the server */
      $zip = new \ZipArchive();
    $zip->open($zip_filename,\ZipArchive::CREATE);

  /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $book_id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $book_id);
    $chapter_q = $query->execute();
    while ($chapter_row = $chapter_q->fetchObject()) {
      $CH_PATH = 'CH' . $chapter_row->number . '/';
      /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_row->id);*/
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_row->id);
      $query->condition('approval_status', 1);
      $example_q = $query->execute();
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $query = \Drupal::database()->select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_row->id);
        $example_files_q = $query->execute();
        while ($example_files_row = $example_files_q->fetchObject()) {
          // $zip->addFile($root_path . $directory_name . '/' . $example_files_row->filepath, $BK_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);

          $fs = \Drupal::service('file_system');

$full_path = $root_path . $directory_name . '/' . $example_files_row->filepath;

// Convert to real filesystem path
$real_path = $fs->realpath($full_path);

// Debug: Check if file exists
if (!file_exists($real_path)) {
    \Drupal::logger('tbc')->error('Missing file: ' . $real_path);
    continue;
}

// Add file to ZIP
$zip->addFile(
    $real_path,
    $BK_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename
);

        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      $user = \Drupal::currentUser();
      if ($user->uid) {
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', ($book_data->book)) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        ob_clean();
        readfile($zip_filename);
        unlink($zip_filename);
      }
      else {
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', ($book_data->book)) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        ob_end_flush();
        ob_clean();
        flush();
        readfile($zip_filename);
        unlink($zip_filename);
      }
    }
    else {
      \Drupal::messenger()->addMessage("There are no examples in this book to download", 'error');
      //drupal_goto('textbook-companion/textbook-run');
return $this->redirect('textbook_companion.run_form', ['book_pref_id' => $book_id]);


    }
  }

 
  
  public function textbook_companion_download_full_chapter() {
    $chapter_id = arg(3);
    $root_path = textbook_companion_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get example data */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
	$chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $chapter_q = $query->execute();
    $chapter_data = $chapter_q->fetchObject();
    $CH_PATH = 'CH' . $chapter_data->number . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved examples */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_id);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 1);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
      $query = db_select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('example_id', $example_row->id);
      $example_files_q = $query->execute();
      while ($example_files_row = $example_files_q->fetchObject()) {
        $zip->addFile($root_path . $example_files_row->filepath, $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
      } //$example_files_row = $example_files_q->fetchObject()
    } //$example_row = $example_q->fetchObject()
	/* unapproved examples */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_id);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
      $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
        ':example_id' => $example_row->id
        ]);
      /*$query = db_select('textbook_companion_example_files');
		$query->fields('textbook_companion_example_files');
		$query->condition('example_id', $example_row->id);
		$example_files_q = $query->execute();*/
      while ($example_files_row = $example_files_q->fetchObject()) {
        $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
      } //$example_files_row = $example_files_q->fetchObject()
    } //$example_row = $example_q->fetchObject()
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename="CH' . $chapter_data->number . '.zip"');
      header('Content-Length: ' . filesize($zip_filename));
      header("Content-Transfer-Encoding: binary");
      header('Expires: 0');
      header('Pragma: no-cache');
      ob_end_flush();
      ob_clean();
      flush();
      readfile($zip_filename);
      unlink($zip_filename);
    } //$zip_file_count > 0
    else {
      drupal_set_message("There are no examples in this chapter to download", 'error');
      drupal_goto('textbook-companion/code-approval/bulk');
    }
  }

  public function textbook_companion_download_full_book() {
    $book_id = arg(3);
    $root_path = textbook_companion_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get example data */
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
	$book_data = db_fetch_object($book_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $book_id);
    $book_q = $query->execute();
    $book_data = $book_q->fetchObject();
    //$zipname = str_replace(' ','_',($book_data->book));
    //$BK_PATH = $zipname . '/';
    $BK_PATH = $book_data->book . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved examples */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $book_id);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $book_id);
    $chapter_q = $query->execute();
    while ($chapter_row = $chapter_q->fetchObject()) {
      $CH_PATH = 'CH' . $chapter_row->number . '/';
      /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_row->id);*/
      $query = db_select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_row->id);
      $query->condition('approval_status', 1);
      $example_q = $query->execute();
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
          ':example_id' => $example_row->id
          ]);
        /*$query = db_select('textbook_companion_example_files');
			$query->fields('textbook_companion_example_files');
			$query->condition('example_id', $example_row->id);
			$example_files_q = $query->execute();*/
        while ($example_files_row = $example_files_q->fetchObject()) {
          $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $BK_PATH . $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
        } //$example_files_row = $example_files_q->fetchObject()
      } //$example_row = $example_q->fetchObject()
		/* unapproved examples */
      /* $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_row->id);*/
      $query = db_select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_row->id);
      $query->condition('approval_status', 0);
      $example_q = $query->execute();
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
          ':example_id' => $example_row->id
          ]);
        /*$query = db_select('textbook_companion_example_files');
			$query->fields('textbook_companion_example_files');
			$query->condition('example_id', $example_row->id);
			$example_files_q = $query->execute();*/
        while ($example_files_row = $example_files_q->fetchObject()) {
          $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $BK_PATH . $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
        } //$example_files_row = $example_files_q->fetchObject()
      } //$example_row = $example_q->fetchObject()
    } //$chapter_row = $chapter_q->fetchObject()
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename="' . str_replace(' ', '_', ($book_data->book)) . '.zip"');
      header('Content-Length: ' . filesize($zip_filename));
      ob_clean();
      readfile($zip_filename);
      unlink($zip_filename);
    } //$zip_file_count > 0
    else {
      drupal_set_message("There are no examples in this book to download", 'error');
      drupal_goto('textbook-companion/code-approval/bulk');
    }
  }

  public function textbook_companion_delete_book() {
    $book_id = arg(2);
    del_book_pdf($book_id);
    drupal_set_message(t('Book schedule for regeneration.'), 'status');
    drupal_goto('code_approval/bulk');
    return;
  }

  public function textbook_companion_ajax() {
    $query_type = arg(2);
    if ($query_type == 'chapter_title') {
      $chapter_number = arg(3);
      $preference_id = arg(4);
      /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE number = %d AND preference_id = %d LIMIT 1", $chapter_number, $preference_id);*/
      $query = db_select('textbook_companion_chapter');
      $query->fields('textbook_companion_chapter');
      $query->condition('number', $chapter_number);
      $query->condition('preference_id', $preference_id);
      $query->range(0, 1);
      $chapter_q = $query->execute();
      if ($chapter_data = $chapter_q->fetchObject()) {
        echo $chapter_data->name;
        return;
      } //$chapter_data = $chapter_q->fetchObject()
    } //$query_type == 'chapter_title'
    else {
      if ($query_type == 'example_exists') {
        $chapter_number = arg(3);
        $preference_id = arg(4);
        $example_number = arg(5);
        $chapter_id = 0;
        /* $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE number = %d AND preference_id = %d LIMIT 1", $chapter_number, $preference_id);*/
        $query = db_select('textbook_companion_chapter');
        $query->fields('textbook_companion_chapter');
        $query->condition('number', $chapter_number);
        $query->condition('preference_id', $preference_id);
        $query->range(0, 1);
        $chapter_q = $query->execute();
        if (!$chapter_data = $chapter_q->fetchObject()) {
          echo '';
          return;
        } //!$chapter_data = $chapter_q->fetchObject()
        else {
          $chapter_id = $chapter_data->id;
        }
        /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND number = '%s' LIMIT 1", $chapter_id, $example_number);*/
        $query = db_select('textbook_companion_example');
        $query->fields('textbook_companion_example');
        $query->condition('chapter_id', $chapter_id);
        $query->condition('number', $example_number);
        $query->range(0, 1);
        $example_q = $query->execute();
        if ($example_data = $example_q->fetchObject()) {
          if ($example_data->approval_status == 1) {
            echo 'Warning! Example already approved. You cannot upload the same example again.';
          }
          else {
            echo 'Warning! Example already uploaded. Delete the example and reupload it.';
          }
          return;
        } //$example_data = $example_q->fetchObject()
      }
    } //$query_type == 'example_exists'
    echo '';
  }

  public function _data_entry_proposal_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 ORDER BY book ASC");*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approval_status', 1);
    $query->orderBy('book', 'ASC');
    $preference_q = $query->execute();
    $sno = 1;
    while ($preference_data = $preference_q->fetchObject()) {
      $proposal_rows[] = [
        $sno++,
        $preference_data->book,
        $preference_data->author,
        $preference_data->isbn,
        l('Edit', 'textbook-companion/dataentry-edit/' . $preference_data->id),
      ];
    } //$preference_data = $preference_q->fetchObject()
	/* check if there are any pending proposals */
    if (!$proposal_rows) {
      drupal_set_message(t('There are no proposals.'), 'status');
      return '';
    } //!$proposal_rows
    $proposal_header = [
      'SNO',
      'Title of the Book',
      'Author',
      'ISBN',
      '',
    ];
    $output = theme('table', [
      'headers' => $proposal_header,
      'rows' => $proposal_rows,
    ]);
    return $output;
  }

  public function dataentry_edit($id = NULL) {
    if ($id) {
      return drupal_get_form('dataentry_edit_form', $id);
    } //$id
    else {
      return 'Access denied';
    }
  }

  public function cheque_proposal_all() {


    $form['#redirect'] = FALSE;
    $form['search_cheque'] = [
      '#type' => 'textfield',
      '#title' => t('Search'),
      '#size' => 48,
    ];
    $form['submit_cheque'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];
    $form['cancel_cheque'] = [
      '#type' => 'markup',
      '#value' => l(t('Cancel'), ''),
    ];


    $count = 20;
    /* get pending proposals to be approved */
    $proposal_rows = [];

    /*$proposal_q = "SELECT * FROM {textbook_companion_proposal} ORDER BY id DESC";*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->orderBy('id', 'DESC');


    /*$pagerquery = pager_query($proposal_q, $count); */
    $pagerquery = $query->extend('PagerDefault')->limit($count)->execute();

    while ($proposal_data = $pagerquery->fetchObject()) {
      /* get preference */

      /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/

      $query = db_select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('proposal_id', $proposal_data->id);
      $query->condition('approval_status', 1);
      $query->range(0, 1);
      $result = $query->execute();
      $preference_data = $result->fetchObject();

      /*$preference_q1 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data1 = db_fetch_object($preference_q1);*/

      $query = db_select('textbook_companion_proposal');
      $query->fields('textbook_companion_proposal');
      $query->condition('uid', $proposal_data->id);
      $query->condition('proposal_status', 1);
      $query->range(0, 1);
      $result = $query->execute();
      $preference_data1 = $result->fetchObject();


      $proposal_status = '';
      switch ($proposal_data->proposal_status) {
        case 0:
          $proposal_status = 'Pending';
          break;
        case 1:
          $proposal_status = 'Approved';
          break;
        case 2:
          $proposal_status = 'Dis-approved';
          break;
        case 3:
          $proposal_status = 'Completed';
          break;
        default:
          $proposal_status = 'Unknown';
          break;
      }
      $proposal_rows[] = [
        date('d-m-Y', $proposal_data->creation_date),
        l($proposal_data->full_name, 'user/' . $proposal_data->uid),
        date('d-m-Y', $proposal_data->completion_date),
        l('Form Submission', 'manage_proposal/paper_submission/' . $proposal_data->id) . ' | ' . l('Cheque Details', 'cheque_contact/status/' . $proposal_data->id),
      ];
    }

    /* check if there are any pending proposals */
    if (!$proposal_rows) {
      drupal_set_message(t('There are no proposals.'), 'status');
      return '';
    }

    $proposal_header = [
      'Date of Submission',
      'Contributor Name',
      'Expected Date of Completion',
      'Status',
    ];
    $output = theme('table', [
      'header' => $proposal_header,
      'rows' => $proposal_rows,
    ]);
    return $output . theme_pager($count);
  }

  // ---Added new functions
   public function textbook_companion_completed_books() {
    $output = '';

    $database = \Drupal::database();
    $query = $database->select('textbook_companion_preference', 'pe');
    $query->fields('pe', ['book', 'author', 'publisher', 'year', 'id']);
    $query->leftJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->fields('po', ['full_name', 'university', 'completion_date']);
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $query->orderBy('po.completion_date');

    $results = $query->execute()->fetchAll();

    if (empty($results)) {
  $output = [
    '#markup' => $this->t('Work has been completed on the following books under the Textbook Companion Project.') .
      '<br><span style="color:red;">' . 
      $this->t('The list below is not the books as named but only are the solved example for DWSIM') .
      '</span>',
  ];
}
else {
  $output = [
    '#markup' => $this->t('Work has been completed on the following books under the Textbook Companion Project.') .
      '<br><span style="color:red;">' .
      $this->t('The list below is not the books as named but only are the solved example for DWSIM.') .
      '</span>',
  ];

        $rows = [];
        $i = count($results);
        foreach ($results as $row) {
            $completion_year = date("Y", $row->completion_date);

            $link = Link::fromTextAndUrl(
                $row->book . ' by ' . $row->author . ', ' . $row->publisher . ', ' . $row->year,
                Url::fromUserInput('/textbook-companion/textbook-run/' . $row->id)
            )->toString();

            $rows[] = [
                $i,
                $link,
                $row->full_name,
                $row->university,
                $completion_year,
            ];
            $i--;
        }

        $header = [
            'No',
            'Title of the Book',
            'Contributor Name',
            'University / Institute',
            'Year of Completion'
        ];

            $output =  [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      //'#empty' => 'no rows found',
    ];    }

    return $output;
}
 public function textbook_companion_download_example($example_id) {
    $root_path = \Drupal::service("textbook_companion_global")->textbook_companion_path();

     $connection = \Drupal::database();

    // Fetch example data
    $example_data = \Drupal::database()->select('textbook_companion_example', 'tce')
      ->fields('tce')
      ->condition('id', $example_id)
      ->execute()
      ->fetchObject();

    // if (!$example_data) {
    //   $this->messenger()->addError($this->t('Example not found.'));
    //   return $this->redirect('/textbook-companion/textbook-run');
    // }

    // Fetch example files
    $example_files = \Drupal::database()->query("
      SELECT * FROM textbook_companion_preference tcp
      JOIN textbook_companion_chapter tcc ON tcp.id = tcc.preference_id
      JOIN textbook_companion_example tce ON tcc.id = tce.chapter_id
      JOIN textbook_companion_example_files tcef ON tce.id = tcef.example_id
      WHERE tcef.example_id = :example_id
    ", [':example_id' => $example_id]);

    $EX_PATH = 'EX' . $example_data->number . '/';
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

    // var_dump($zip_filename);die;

    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);


    foreach ($example_files as $file_row) {
      $zip->addFile($root_path . $file_row->directory_name . '/' . $file_row->filepath, $EX_PATH . $file_row->filename);
    }
// 
    // var_dump($root_path . $file_row->directory_name . '/' . $file_row->filepath, $EX_PATH . $file_row->filename );die;
    
        $zip_file_count = $zip->numFiles;

    $zip->close();

    if ($zip_file_count > 0) {
      $response = new Response(file_get_contents($zip_filename));
      $response->headers->set('Content-Type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', 'attachment; filename="EX' . $example_data->number . '.zip"');
      $response->headers->set('Content-Length', filesize($zip_filename));

      unlink($zip_filename);
      return $response;
    } 
    else {
      $this->messenger()->addError($this->t('There are no files in this example to download.'));
      // return $this->redirect('/textbook-companion/textbook-run');
    }
  }





}
