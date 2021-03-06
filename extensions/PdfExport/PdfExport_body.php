<?php
// define maximum width of images
if(!defined('MAX_IMAGE_WIDTH'))
	define("MAX_IMAGE_WIDTH", 670);
 
if ( !defined( 'MEDIAWIKI' ) ) 
     die();
 
global $wgPdfExportAttach, $wgPdfExportHttpsImages;
$wgPdfExportAttach = false; // set to true if you want output as an attachment
$wgPdfExportHttpsImages = false; // set to true if page is on a HTTPS server and contains images that are on the HTTPS server and also
                                 // reachable with HTTP
 
        class SpecialPdf extends SpecialPage {
                var $title;
                var $article;
                var $html;
                var $parserOptions;
                var $bhtml;
	        public $iswindows;
 
	        function SpecialPdf() {
		        global $iswindows;
                        parent::__construct( 'PdfPrint');

                        //SpecialPage::SpecialPage( 'PdfPrint' );
                        $os = getenv ("SERVER_SOFTWARE");
                        $iswindows = strstr ($os, "Win32");
                }
 
                public function save1page ( $page ) {
		        global $wgUser;
                        global $wgParser;
                        global $wgScriptPath;
                        global $wgServer;
                        global $wgPdfExportHttpsImages;
 
			$title = Title::newFromText( $page );
			if( is_null( $title ) ) { 
			  return null;
                        }
/*
			if( !$title->userCanRead() ){
                          return null;
                        }
*/
                        $article = new Article ($title);
                        $parserOptions = ParserOptions::newFromUser( $wgUser );
                        $parserOptions->setEditSection( false );
                        $parserOptions->setTidy(true);
                        $wgParser->mShowToc = false;
                        $parserOutput = $wgParser->parse( $article->preSaveTransform( $article->getContent() ) ."\n\n",
                                        $title, $parserOptions );
 
                        $bhtml = $parserOutput->getText();
                        // XXX Hack to thread the EUR sign correctly
                        $bhtml = str_replace(chr(0xE2) . chr(0x82) . chr(0xAC), chr(0xA4), $bhtml);
                        $bhtml = utf8_decode($bhtml);
 
                        // add the '"'. so links pointing to other wikis do not get erroneously converted.
                        $bhtml = str_replace ('"'.$wgScriptPath, '"'.$wgServer . $wgScriptPath, $bhtml);
                        $bhtml = str_replace ('/w/',$wgServer . '/w/', $bhtml);

// Comment out previous two code lines if wiki is on the root folder and uncomment the following lines
// global $wgUploadPath,$wgScript;
// $bhtml = str_replace ($wgUploadPath, $wgServer.$wgUploadPath,$bhtml);
// if (strlen($wgScriptPath)>0)
// 	$pathToTitle=$wgScriptPath;
// else $pathToTitle=$wgScript;
//	$bhtml = str_replace ("href=\"$pathToTitle", 'href="'.$wgServer.$pathToTitle, $bhtml);

                        // removed heights of images
                        $bhtml = preg_replace ('/height="\d+"/', '', $bhtml);
                        // set upper limit for width
                        $bhtml = preg_replace ('/width="(\d+)"/e', '"width=\"".($1> MAX_IMAGE_WIDTH ?  MAX_IMAGE_WIDTH : $1)."\""', $bhtml);
 
                        if ($wgPdfExportHttpsImages) {
                            $bhtm = str_replace('img src=\"https:\/\/','img src=\"http:\/\/', $bhtml);
                        }
 
                        $html = "<html><head><title>" . utf8_decode($page) . "</title></head><body>" . $bhtml . "</body></html>";
                        return $html;
		}
 
                function outputpdf ($pages, $landscape, $permissions, $fontface, $fontsize, $margintop, $marginsides, $marginbottom, $size, $filename) {
		        global $iswindows;
		        global $wgPdfExportAttach;
                        global $wgRequest;
			global $wgPdfExportBackground;

                        $returnStatus = 0;
			$pagestring = "";
                        $pagefiles = array();
                        $foundone = false;
 
                        foreach ($pages as $pg) {
			  $pagestring .= $this->save1page ($pg);
                          if ($pagestring == null) {
			    continue;
                          }
                          }
 
                        if ($pagestring == "") {
			  return;
                        }
                        putenv("HTMLDOC_NOCGI=1");
 
                        # Write the content type to the client...
                        header("Content-Type: application/pdf");
			header(sprintf('Content-Disposition: attachment; filename="' . $filename . '"', $wgRequest->getVal('page')));
 
			$htmldoc_descriptorspec = array(
							// stdin is a pipe that the child will read from
							0 => array("pipe", "r"),  
							// stdout is a pipe that the child will write to */
							1 => array("pipe", "w"));
                        # Run HTMLDOC to provide the PDF file to the user...
			$htmldoc_process = proc_open("htmldoc -t pdf14 --charset iso-8859-15 --color --quiet --jpeg --bodyfont " . $fontface . " --textfont " . $fontface . " --headingfont " . $fontface . " --fontsize " . $fontsize . " --bodyimage " . $wgPdfExportBackground . " --top " . $margintop . " --left " . $marginsides . " --right " . $marginsides . " --bottom " . $marginbottom . " --permissions " . $permissions . " --size " . $size . " " . $landscape . "--webpage -", $htmldoc_descriptorspec, $pipes);

			fwrite($pipes[0], $pagestring);
			fclose($pipes[0]);
 
			fpassthru($pipes[1]);
			fclose($pipes[1]);
 
			$returnStatus = proc_close($htmldoc_process);
 
                        if($returnStatus == 1)
                        {
                                error_log("Generating PDF failed, check path to HTMLDoc, return status was:" . $returnStatus, 0);
                        }
                        flush();
                        foreach ($pagefiles as $pgf) {
			  unlink ($pgf);
			}
                }  
 
 
 
                function execute( $par ) {
		        global $wgRequest;
                        global $wgOut; 

                        wfLoadExtensionMessages ('PdfPrint');
                        $dopdf = false;
                        if ($wgRequest->wasPosted()) {
			  $pagel = $wgRequest->getText ('pagel');
		          $pages = array_filter( explode( "\n", $pagel ), 'wfFilterPage1' );
		          $filename = $wgRequest->getText ('filename');
			  $fontface = $wgRequest->getText ('fontface');
			  $fontsize = $wgRequest->getText ('fontsize');
			  $size = $wgRequest->getText ('Size', 'Letter');
			  $margintop = $wgRequest->getText ('margintop');
			  $marginsides = $wgRequest->getText ('marginsides');
			  $marginbottom = $wgRequest->getText ('marginbottom');
			  $permissions = $wgRequest->getVal ('permissions');
                          $orientations = $wgRequest->getVal ('orientation');
                          if ($orientations == 'landscape') {
			    $orientation = " --landscape --browserwidth 1200 ";
                          }
                          else {
                            $orientation = " --portrait ";
                          }
                          $dopdf = true;
			}
                        else {
                          $page = isset( $par ) ? $par : $wgRequest->getText( 'page' );
                          if ($page != "") {
			    $dopdf = true; 
                          }
                          $pages = array ($page);
			  $fontface = "times";
			  $fontsize = "11";
			  $margintop = "20mm";
			  $marginsides = "20mm";
			  $marginbottom = "20mm";
			  $permissions = "all";

                          $orientation = " --portrait ";
                          $size = "A4";
                          $filename = "%s.pdf";
                        }

			if ($dopdf) {
			  $wgOut->setPrintable();
			  $wgOut->disable();
 
			  $this->outputpdf ($pages, $orientation, $permissions, $fontface, $fontsize, $margintop, $marginsides, $marginbottom, $size, $filename);
                          return;
                        }
 
			$self = SpecialPage::getTitleFor( 'PdfPrint' );
			$wgOut->addHtml( wfMessage( 'pdf_print_text' )->format() );
 
			$form = Xml::openElement( 'form', array( 'method' => 'post',
								 'action' => $self->getLocalUrl( 'action=submit' ) ) );
 
			$form .= Xml::openElement( 'textarea', array( 'name' => 'pagel', 'cols' => 40, 'rows' => 10 ) );
			$form .= Xml::closeElement( 'textarea' );
			$form .= '<br />';
                        $form .= '<br />' . wfMessage('pdf_size')->text() .": ";
	                $form .= Xml::listDropDown ('Size', wfMessage ('pdf_size_options')->text(),'', wfMessage('pdf_size_default')->text());
			$form .= Xml::radioLabel(wfMessage ('pdf_portrait')->text(), 'orientation' , 'portrait' , 'portrait', true);
			$form .= Xml::radioLabel(wfMessage ('pdf_landscape')->text(), 'orientation' , 'landscape' , 'landscape', false);
			$form .= '<br />';
			$form .= wfMessage ('pdf_fontface_label')->text().": ";
			$form .= Xml::listDropDown ('fontface', wfMessage ('pdf_fontface_options')->text(),'', wfMessage('pdf_fontface_default')->text());
			$form .= " " .wfMessage ('pdf_fontsize_label')->text().": ";
			$form .= Xml::openElement( 'input', array( 'type'=>'text', 'name' => 'fontsize', 'value' => '11' ) );
			$form .= Xml::closeElement( 'input' );
			$form .= '<br />';
			$form .= wfMessage ('pdf_permissions_label')->text().":";
			$form .= Xml::radioLabel(wfMessage ('pdf_permissions_all')->text(), 'permissions' , 'all' , 'all', true);
			# "no-copy,print" results in a not-printable PDF (tested in KPDF & Acrobat Reader 9)
			//$form .= Xml::radioLabel(wfMessage ('pdf_permissions_print')->text(), 'permissions' , 'no-copy,print' , 'no-copy,print', false);
			$form .= Xml::radioLabel(wfMessage ('pdf_permissions_none')->text(), 'permissions' , 'none' , 'none', false);
			$form .= '<br />';
			$form .= wfMessage ('pdf_margins_label')->text().":";
			$form .= '<ul>';
			$form .= ' <li>';
			$form .= Xml::openElement( 'input', array( 'type'=>'text', 'name' => 'margintop', 'value' => '20mm' ) );
			$form .= Xml::closeElement( 'input' );
			$form .= ' (' . wfMessage ('pdf_margins_label_top')->text() . ')';
			$form .= ' </li>';
			$form .= ' <li>';
			$form .= Xml::openElement( 'input', array( 'type'=>'text', 'name' => 'marginsides', 'value' => '20mm' ) );
			$form .= Xml::closeElement( 'input' );
			$form .= ' (' . wfMessage ('pdf_margins_label_sides')->text() . ')';
			$form .= ' </li>';
			$form .= ' <li>';
			$form .= Xml::openElement( 'input', array( 'type'=>'text', 'name' => 'marginbottom', 'value' => '20mm' ) );
			$form .= Xml::closeElement( 'input' );
			$form .= ' (' . wfMessage ('pdf_margins_label_bottom')->text() . ')';
			$form .= ' </li>';
			$form .= '</ul>';
			$form .= '<br />';
			// input field for name of PDF
			$form .= wfMessage ('pdf_filename')->text().": ";
			$form .= Xml::openElement( 'input', array( 'type'=>'text', 'name' => 'filename', 'value' => 'print.pdf' ) );
			$form .= Xml::closeElement( 'input' );
			$form .= '<br /><br />';
			$form .= Xml::submitButton( wfMessage( 'pdf_submit' )->text() );
	                $form .= Xml::closeElement( 'form' );
	                $wgOut->addHtml( $form );
 
        }
}
 
function wfFilterPage1( $page ) {
	return $page !== '' && $page !== null;
}
