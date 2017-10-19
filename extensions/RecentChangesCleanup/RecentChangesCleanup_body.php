<?php
 class RecentChangesCleanup extends SpecialPage
 {
    
    function __construct()
    {
       //parent::__construct( 'RecentChangesCleanup' );
       // restrict to sysops
       parent::__construct('RecentChangesCleanup', 'editinterface'); // restricts to sysops?
       //wfLoadExtensionMessages('RecentChangesCleanup');
    }
    
    function execute($par)
    {
       global $wgUser, $wgRequest, $wgOut, $wgDBprefix;
       
       $min_edit_count = 100;
       // limit to editors with 100 edits

       $edit_count = $wgUser->getEditCount();
       if (!($edit_count > $min_edit_count || $wgUser->isAllowed('recentchangescleanup') || $wgUser->isAllowed('protect'))) {
          $wgOut->addHTML(wfMessage('rc-cleanup-access-error', $min_edit_count)->text());
          return;
       }
       
       $this->setHeaders();
       $wgOut->setPagetitle("Recent Changes Cleanup");
       $max_results = 250;
       $table_name  = addslashes($wgDBprefix . 'recentchanges');
       $action      = htmlentities($wgRequest->gettext('action'));
       $id          = htmlentities($wgRequest->gettext('id'));
       
       $dbw = wfGetDB(DB_MASTER);
       if ($action == 'markasbot') {
          $dbw->update('recentchanges', array(
             /* SET */
             'rc_bot' => 1
          ), array(
             /* WHERE */
             'rc_id' => $id
          ), "");
       }
       
       if ($action == 'markasnotbot') {
          $dbw->update('recentchanges', array(
             /* SET */
             'rc_bot' => 0
          ), array(
             /* WHERE */
             'rc_id' => $id
          ), "");
       }
       
       $wgOut->addHTML('<table  border="1" cellspacing="0" cellpadding="3">');
       $wgOut->addHTML('<tr bgcolor="#FFFFE0"><td width="85"><b>'. wfMessage('rc-cleanup-show')->text(). '/'. wfMessage('rc-cleanup-hide')->text() .
	    '</b></td><td><b>'.wfMessage('rc-cleanup-header-user')->text().'</b></td><td><b>'.wfMessage('rc-cleanup-header-action')->text().
		'</b></td><td><b>'.wfMessage('rc-cleanup-header-comment')->text().'</b></td></tr>');

       $skin = $wgUser->getSkin();
       $row_color = 0;
       
       $dbr = wfGetDB(DB_SLAVE);
       $res = $dbr->select('recentchanges', // $table
          array(
          'rc_id',
          'rc_bot',
          'rc_user_text',
          'rc_title',
          'rc_comment'
       ), // $vars (columns of the table)
          '', // $conds
          __METHOD__, // $fname = 'Database::select',
          array(
          'ORDER BY' => 'rc_id DESC',
          "LIMIT" => $max_results
       ) // $options = array()
          );
       
       foreach ($res as $row) {
          
          $row_color    = $row_color + 1;
          $rc_id        = $row->rc_id;
          $rc_bot       = $row->rc_bot;
          $rc_user_text = $row->rc_user_text;
          $rc_title     = $row->rc_title;
          $rc_comment   = $row->rc_comment;
          
          if (($row_color % 2) == 0) {
             $rcolor = "#F0F0F0";
          } else {
             $rcolor = "#FFFFFF";
          }
          $wgOut->addHTML('<tr bgcolor="' . $rcolor . '">');
          
          if ($rc_bot == 0) {
             $wgOut->addHtml('<td>' . $skin->link($this->getTitle(), wfMessage('rc-cleanup-hide')->text(), array(), array(
                'action' => 'markasbot',
                'id' => $rc_id
             )) . '</td>');
          } else {
             $wgOut->addHtml('<td><b>' . $skin->link($this->getTitle(), wfMessage('rc-cleanup-show')->text() , array(), array(
                'action' => 'markasnotbot',
                'id' => $rc_id
             )) . '</b></td>');
          }
          
          $wgOut->addHTML('<td>' . $rc_user_text . '</td>' . '<td>' . $rc_title . '</td>' . '<td>' . htmlspecialchars($rc_comment) . ' &nbsp;</td>' . '</tr>');
       }
       $wgOut->addHTML('</table>');
       $dbr->freeResult($res);
    }
    // function execute
 } // class
?>
