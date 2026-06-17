<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require_once "../init_without_validate.php";
require_once '../assess2/AssessStandalone.php';

$assessver = 2;
$courseUIver = 2;
$assessUIver = 2;
$inQuestionTesting = true;

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = _("Test Question");
$asid = 0;
$_SESSION['mathdisp'] = 1;
$_SESSION['graphdisp'] = 1;
	//CHECK PERMISSIONS AND SET FLAGS
if (1==2) {
 	$overwriteBody = 1;
	$body = _("You need to log in as a teacher to access this page");
} else {
	//data manipulation here
    $useeditor = 1;
    

	if (isset($_GET['onlychk']) && $_GET['onlychk']==1) {
		$onlychk = 1;
	} else {
		$onlychk = 0;
	}
  	$qsetid = Sanitize::onlyInt($_GET['qsetid']);
	if (isset($_GET['formn']) && isset($_GET['loc'])) {
		$formn = Sanitize::encodeStringForJavascript($_GET['formn']);
		$loc = Sanitize::encodeStringForJavascript($_GET['loc']);
		if (isset($_GET['checked']) || isset($_GET['usecheck'])) {
			$chk = "&checked=0";
		} else {
			$chk = '';
		}
		if ($onlychk==1) {
		  $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','$loc',true);";
		} else {
		  $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','$loc');";
		}
	}

    $query = "SELECT * FROM myopenmath WHERE id = :id";
	$stm = $MOMDBH->prepare($query);
	$stm->execute(array(':id'=>$qsetid));
	$line1 = $stm->fetch(PDO::FETCH_ASSOC);
    if ($line1 === false) {
        echo _('Invalid question ID');
        exit;
    }
    $isquestionauthor = false;
    $seed = rand(0,10000);
    
    $input = '{"email":"u@abc.co","id":"36","uniqueid":"1699501100492899","adddate":"1699501100","lastmoddate":"1699501137","ownerid":"1","author":"Nguyen,Vu","userights":"0","license":"1","description":"Algebra problem","qtype":"multipart","control":"","qcontrol":"","qtext":"","answer":"","solution":"","extref":"","hasimg":"0","deleted":"0","avgtime":"0","ancestors":"","ancestorauthors":"","otherattribution":"","importuid":"","replaceby":"0","broken":"0","solutionopts":"6","sourceinstall":"","meantimen":"1","meantime":"19","vartime":"0","meanscoren":"1","meanscore":"50","varscore":"0","isrand":"1"}';
    
    $line = json_decode($input, true);

    $line["qtype"] = $line1["type"];
    $line["control"] = $line1["control"];
    $line["qtext"] = $line1["qtext"];
    $line["solution"] = $line1["solution"];
    $line["id"] = $qsetid;

  $a2 = new AssessStandalone($DBH);
  $a2->setQuestionData($line["id"], $line);

  $hasSeqParts = preg_match('~(<p[^>]*>(<[^>]*>)*|\\n\s*\\n|<br\s*/?><br\s*/?>)\s*///+\s*((<[^>]*>)*</p[^>]*>|\\n\s*\\n|<br\s*/?><br\s*/?>)~', $line['qtext']);

  $qn = 27;  //question number to use during testing
  if (isset($_POST['state'])) {
    $state = json_decode($_POST['state'], true);
    $seed = intval($state['seeds'][$qn]);
  } else {
    if (isset($_GET['seed'])) {
  		$seed = Sanitize::onlyInt($_GET['seed']);
  	} else {
  		$seed = rand(0,10000);
  	}
    $state = array(
      'seeds' => array($qn => $seed),
      'qsid' => array($qn => $qsetid),
      'stuanswers' => array(),
      'stuanswersval' => array(),
      'scorenonzero' => array(($qn+1) => -1),
      'scoreiscorrect' => array(($qn+1) => -1),
      'partattemptn' => array($qn => array()),
      'rawscores' => array($qn => array())
    );
  }
  $a2->setState($state);

	if (isset($_POST['toscoreqn'])) {
    $toscoreqn = json_decode($_POST['toscoreqn'], true);
    $parts_to_score = array();
    if (isset($toscoreqn[$qn])) {
      foreach ($toscoreqn[$qn] as $pn) {
        $parts_to_score[$pn] = true;
      };
    }
    $res = $a2->scoreQuestion($qn, $parts_to_score);

		$score = implode('~', $res['scores']);
		$page_scoreMsg =  "<p>"._("Score on last answer: ").Sanitize::encodeStringForDisplay($score)."/1</p>\n";
    if (!empty($res['errors'])) {
      $page_scoreMsg .= '<ul class="small">';
      foreach ($res['errors'] as $err) {
        $page_scoreMsg .= '<li>'.Sanitize::encodeStringForDisplay($err).'</li>';
      }
      $page_scoreMsg .= '</ul>';
    }
	} else {
		$page_scoreMsg = "";
		$_SESSION['choicemap'] = array();
	}
  $cid = Sanitize::courseId($_GET['cid'] ?? 0);
	$page_formAction = "testquestion2.php?cid=$cid&qsetid=".Sanitize::encodeUrlParam($qsetid);

	if (isset($_POST['usecheck'])) {
		$page_formAction .=  "&checked=".Sanitize::encodeUrlParam($_GET['usecheck']);
	} else if (isset($_GET['checked'])) {
		$page_formAction .=  "&checked=".Sanitize::encodeUrlParam($_GET['checked']);
	}
	if (isset($_GET['formn'])) {
		$page_formAction .=  "&formn=".Sanitize::encodeUrlParam($_GET['formn']);
		$page_formAction .=  "&loc=".Sanitize::encodeUrlParam($_GET['loc']);
	}
	if (isset($_GET['onlychk'])) {
		$page_formAction .=  "&onlychk=".Sanitize::encodeUrlParam($_GET['onlychk']);
	}
	if (isset($_GET['fixedseeds'])) {
		$page_formAction .=  "&fixedseeds=1";
	}

	$lastmod = date("m/d/y g:i a",$line['lastmoddate']);

	if (isset($CFG['AMS']['showtips'])) {
		$showtips = $CFG['AMS']['showtips'];
	} else {
		$showtips = 1;
	}
	if (isset($CFG['AMS']['eqnhelper'])) {
		$eqnhelper = $CFG['AMS']['eqnhelper'];
	} else {
		$eqnhelper = 4;
	}
	$resultLibNames = $DBH->prepare("SELECT imas_libraries.name,imas_users.LastName,imas_users.FirstName,imas_libraries.id,imas_users.id FROM imas_libraries,imas_library_items,imas_users  WHERE imas_libraries.id=imas_library_items.libid AND imas_libraries.deleted=0 AND imas_library_items.deleted=0 AND imas_library_items.ownerid=imas_users.id AND imas_library_items.qsetid=:qsetid");
	$resultLibNames->execute(array(':qsetid'=>$qsetid));
}

/******* begin html output ********/
$_SESSION['coursetheme'] = $coursetheme;
$flexwidth = true; //tells header to use non _fw stylesheet
$nologo = true;

$useeqnhelper = $eqnhelper;
$lastupdate = '20221027';
$placeinhead = '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/assess2/vue/css/index.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/assess2/vue/css/chunk-common.css?v='.$lastupdate.'" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/assess2/print.css?v='.$lastupdate.'" media="print">';
if (!empty($CFG['assess2-use-vue-dev'])) {
  $placeinhead .= '<script src="'.$staticroot.'/mathquill/mathquill.js?v=112822" type="text/javascript"></script>';
  $placeinhead .= '<script src="'.$staticroot.'/javascript/drawing.js?v=041920" type="text/javascript"></script>';
  $placeinhead .= '<script src="'.$staticroot.'/javascript/AMhelpers2.js?v=071122" type="text/javascript"></script>';
  $placeinhead .= '<script src="'.$staticroot.'/javascript/eqntips.js?v=041920" type="text/javascript"></script>';
  $placeinhead .= '<script src="'.$staticroot.'/javascript/mathjs.js?v=20230729" type="text/javascript"></script>';
  $placeinhead .= '<script src="'.$staticroot.'/mathquill/AMtoMQ.js?v=071122" type="text/javascript"></script>';
  $placeinhead .= '<script src="'.$staticroot.'/mathquill/mqeditor.js?v=021121" type="text/javascript"></script>';
  $placeinhead .= '<script src="'.$staticroot.'/mathquill/mqedlayout.js?v=071122" type="text/javascript"></script>';
} else {
  $placeinhead .= '<script src="'.$staticroot.'/mathquill/mathquill.min.js?v=112822" type="text/javascript"></script>';
  $placeinhead .= '<script src="'.$staticroot.'/javascript/assess2_min.js?v=20231106" type="text/javascript"></script>';
}

$placeinhead .= '<script src="'.$staticroot.'/javascript/assess2supp.js?v=041522" type="text/javascript"></script>';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="'.$staticroot.'/mathquill/mathquill-basic.css?v=021823">
  <link rel="stylesheet" type="text/css" href="'.$staticroot.'/mathquill/mqeditor.css?v=081122">';
$placeinhead .= '<style>form > hr { border: 0; border-bottom: 1px solid #ddd;}</style>';
$placeinhead .= '<script>
  function loadNewVersion() {
    location.href = location.href.replace(/&seed=\w+/g,"");
  }
  function showAllParts(seed) {
    location.href = location.href.replace(/&seed=\w+/g,"") + "&seed=" + seed + "&showallparts=true";
  }
  function showPartSteps(seed) {
    location.href = location.href.replace(/&seed=\w+/g,"").replace(/&showallparts=\w+/,"") + "&seed=" + seed;
  }
  function dellibitems(libid,uid,el) {
      $.post({
          url: window.location.href,
          data: {dellibitems: 1, libid: libid, uid: uid}
      }).done(function(msg) {
          $(el).parent().slideUp();
      });
  }
  </script>';
require_once "../header.php";

if ($overwriteBody==1) {
	echo $body;
} else { //DISPLAY BLOCK HERE
	$useeditor = 1;
	$brokenurl = $GLOBALS['basesiteurl'] . "/course/savebrokenqflag.php?qsetid=".Sanitize::encodeUrlParam($qsetid).'&flag=';
	?>
	<script type="text/javascript">
		var BrokenFlagsaveurl = '<?php echo $brokenurl;?>';
		function submitBrokenFlag(tagged) {
		  url = BrokenFlagsaveurl + tagged;
		  if (window.XMLHttpRequest) {
		    req = new XMLHttpRequest();
		  } else if (window.ActiveXObject) {
		    req = new ActiveXObject("Microsoft.XMLHTTP");
		  }
		  if (typeof req != 'undefined') {
		    req.onreadystatechange = function() {submitBrokenFlagDone(tagged);};
		    req.open("GET", url, true);
		    req.send("");
		  }
		}

		function submitBrokenFlagDone(tagged) {
		  if (req.readyState == 4) { // only if req is "loaded"
		    if (req.status == 200) { // only if "OK"
			    if (req.responseText=='OK') {
				    toggleBrokenFlagmsg(tagged);
			    } else {
				    alert(req.responseText);
				    alert("<?php echo _('Oops, error toggling the flag'); ?>");
			    }
		    } else {
			   alert("<?php echo _('Couldn\'t save changes:'); ?>\n"+ req.status + "\n" +req.statusText);
		    }
		  }
		}
		function toggleBrokenFlagmsg(tagged) {
			document.getElementById("brokenmsgbad").style.display = (tagged==1)?"block":"none";
			document.getElementById("brokenmsgok").style.display = (tagged==1)?"none":"block";
			if (tagged==1) {alert("<?php echo _('Make sure you also contact the question author or support so they know why you marked the question as broken'); ?>");}
		}

		$(window).on('beforeunload', function() {
			if (window.opener && !window.opener.closed  && window.opener.sethighlightrow) {
				window.opener.sethighlightrow(-1);
			}
		});
	</script>
	<?php
	if (isset($_GET['formn']) && isset($_GET['loc'])) {
		echo '<p><span id="prev"></span> <span id="next"></span> <span id="chkspan"></span> <span id="remaining"></span></p>';
		echo "<script type=\"text/javascript\">";
		echo "var numchked = -1;";
    echo "$(function() {";
		echo "if (window.opener && !window.opener.closed && window.opener.sethighlightrow && window.opener.getnextprev) {";
		echo " window.opener.sethighlightrow(\"$loc\"); ";
		echo $page_onlyChkMsg;
    echo 'var next = document.getElementById("next");';
    echo 'var prev = document.getElementById("prev");';
    echo 'var remaining = document.getElementById("remaining");';
    echo 'var chkspan = document.getElementById("chkspan");';
		echo " if (prevnext[0][1]>0){
				  prev.innerHTML = '<a href=\"testquestion2.php?cid=$cid$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[0][0]+'&qsetid='+prevnext[0][1]+'\">"._("Prev")."</a>';
			  } else {
				  prev.innerHTML = '"._("Prev")."';
			  }
			  if (prevnext[1][1]>0){
				  next.innerHTML = '<a href=\"testquestion2.php?cid=$cid$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[1][0]+'&qsetid='+prevnext[1][1]+'\">"._("Next")."</a> ';
			  } else {
				  next.innerHTML = '"._("Next")." ';
			  }
			  if (prevnext[2]!=null) {
			  	chkspan.innerHTML = ' <span id=\"numchked\">'+prevnext[2]+'</span> "._("checked")."';
				  numchked = prevnext[2];
			  }
			  if (prevnext[3]!=null) {
			  	remaining.innerHTML = ' '+prevnext[3]+' "._("remaining")."';
			  }
			}
    });
			</script>";
	}

	if (isset($_GET['checked'])) {
		echo "<p id=usecheckwrap><input type=\"checkbox\" name=\"usecheck\" id=\"usecheck\" value=\""._("Mark Question for Use")."\" onclick=\"parentcbox.checked=this.checked;togglechk(this.checked)\" ";
		echo "/> "._("Mark Question for Use")."</p>";
		echo "
		  <script type=\"text/javascript\">
		  var parentcbox = opener.document.getElementById(\"$loc\");
		  if (!parentcbox) {
		  	$('#usecheckwrap').hide();
		  } else {
		  	$('#usecheckwrap').show();
		  	document.getElementById(\"usecheck\").checked = parentcbox.checked;
		  }
		  function togglechk(ischk) {
			  if (numchked!=-1) {
				if (ischk) {
					numchked++;
				} else {
					numchked--;
				}
				document.getElementById(\"numchked\").innerHTML = numchked;
              }
              if (parentcbox) {
                  opener.$(parentcbox).trigger('change');
              }
		  }
		  </script>";
	}
	if (isset($_GET['fixedseeds'])) {
		echo "<p id=\"fixedseedbox\" style=\"display:none\">";
		echo "Seed: $seed. <input type=\"checkbox\" name=\"useinfixed\" id=\"useinfixed\" onclick=\"chguseinfixed(this.checked)\" ";
		echo "/> Include in fixed seed list</p>";
		echo '<script type="text/javascript">
		$(function() {
			var dofixed = opener.document.getElementById("fixedseedwrap").style.display;
			if (dofixed!="none") {
				var fixedseedlist = opener.document.getElementById("fixedseeds").value;
				if (fixedseedlist.match(/\b'.$seed.'\b/)) {
					$("#useinfixed").prop("checked",true);
				}
				$("#fixedseedbox").show();
			}
		});
		function chguseinfixed(state) {
			var fixedseedlist = opener.document.getElementById("fixedseeds").value;
			if (state==true) {
				if (!fixedseedlist.match(/\b'.$seed.'\b/)) {
					if (fixedseedlist=="") {
						fixedseedlist = "'.$seed.'";
					} else {
						fixedseedlist += ",'.$seed.'";
					}
				}
			} else {
				fixedseedlist = fixedseedlist.replace(/\b'.$seed.'(,|$)/,"").replace(/,$/,"");
			}
			opener.document.getElementById("fixedseeds").value = fixedseedlist;
		}
		</script>';
	}

	echo $page_scoreMsg;
	echo '<script type="text/javascript"> function whiteout() { e=document.getElementsByTagName("div");';
	echo 'for (i=0;i<e.length;i++) { if (e[i].className=="question") {e[i].style.backgroundColor="#fff";}}}</script>';
	echo "<form method=post class=\"questionwrap\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"return dopresubmit($qn,false)\">\n";
	echo "<input type=hidden name=seed value=\"$seed\">\n";

  // DO DISPLAY
  echo '<hr/>';
  $starttime = microtime(true);
  $disp = $a2->displayQuestion($qn, [
    'showans' => true,
    'showallparts' => ($hasSeqParts && !empty($_GET['showallparts']))
  ]);
  $gentime = microtime(true) - $starttime;
  if (isset($_SESSION['userprefs']['useeqed']) && $_SESSION['userprefs']['useeqed'] == 0) {
      $disp['jsparams']['noMQ'] = true;
  }
  if (!empty($disp['errors'])) {
    echo '<ul class="small">';
    foreach ($disp['errors'] as $err) {
      echo '<li>'.Sanitize::encodeStringForDisplay($err).'</li>';
    }
    echo '</ul>';
  }
  echo '<div class="questionpane">';
  echo '<div class="question" id="questionwrap'.$qn.'">';
  echo $disp['html'];
  echo '</div></div>';
  echo '<script>$(function() {
    initq('.$qn.','.json_encode($disp['jsparams']).');
  });</script>';
  echo '<input type=hidden name=toscoreqn value=""/>';
  echo '<input type=hidden name=state value="'. Sanitize::encodeStringForDisplay(json_encode($a2->getState())) .'" />';
	echo '<hr/>';
  echo '<div class="submitbtnwrap">';
  echo "<input type=submit class=\"primary\" value=\""._("Submit")."\">";
  if ($hasSeqParts) {
    if (!empty($_GET['showallparts'])) {
      echo '<button type=button onclick="showPartSteps('.$seed.')">'._('Show steps').'</button>';
    } else {
      echo '<button type=button onclick="showAllParts('.$seed.')">'._('Show all parts').'</button>';
    }
  }
  echo '<button type=button onclick="loadNewVersion()">'._('New Version').'</button>';
  echo '</div>';
	echo "</form>\n";

	if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
		$sendtype = 'msg';
		$sendtitle = (_('Message owner'));
		$sendcid = $CFG['GEN']['sendquestionproblemsthroughcourse'];
	} else {
		$sendtype = 'email';
		$sendtitle = _('Email owner');
		$sendcid = $cid;
	}
	if (isset($CFG['GEN']['qerrorsendto'])) {
		if (is_array($CFG['GEN']['qerrorsendto'])) {
			if (empty($CFG['GEN']['qerrorsendto'][3])) { //if not also sending to owner
				$sendtype = $CFG['GEN']['qerrorsendto'][1];
			}
			$sendtitle = $CFG['GEN']['qerrorsendto'][2];
		} else {
			$sendtype = 'email';
			$sendtitle = _('Contact support');
		}
	}

	printf("<p>"._("Question ID:")." %s.  ", Sanitize::encodeStringForDisplay($qsetid));
	echo '<span class="small subdued">'._('Seed:').' '.Sanitize::onlyInt($seed) . '.</span> ';
    echo '<span class="small subdued">'._('Generated in ').round(1000*$gentime).'ms</span> ';
  if ($line['ownerid'] == $userid) {
    echo '<a href="moddataset.php?cid='. Sanitize::courseId($cid) . '&id=' . Sanitize::onlyInt($qsetid).'" target="_blank">';
    echo _('Edit Question') . '</a>';
  } else {
	  echo "<a href=\"#\" onclick=\"GB_show('$sendtitle','$imasroot/course/sendmsgmodal.php?sendtype=$sendtype&cid=" . Sanitize::courseId($sendcid) . '&quoteq='.Sanitize::encodeUrlParam("0-{$qsetid}-{$seed}-reperr-{$assessver}"). "',800,'auto',true,'',null,{label:'"._('Send Message')."',func:'sendmsg'})\">$sendtitle</a> "._("to report problems");
  }
  echo '</p>';

	printf("<p>"._("Description:")." %s</p><p>"._("Author:")." <span class='pii-full-name'>%s</span></p>",
        Sanitize::encodeStringForDisplay($line['description']),
        Sanitize::encodeStringForDisplay($line['author']));
	echo "<p>"._("Last Modified:")." $lastmod</p>";
	if ($line['deleted']==1) {
		echo '<p class=noticetext>'._('This question has been marked for deletion.  This might indicate there is an error in the question. ');
		echo _('It is recommended you discontinue use of this question when possible').'</p>';
	}
	if ($line['replaceby']>0) {
	  echo '<p class=noticetext>'.sprintf(_('This message has been marked as deprecated, and it is recommended you use question ID %s instead.  You can find this question by searching all libraries with the ID number as the search term'),$line['replaceby']).'</p>';
	}

	echo '<p id="brokenmsgbad" class=noticetext style="display:'.(($line['broken']==1)?"block":"none").'">'._('This question has been marked as broken.  This indicates there might be an error with this question.  Use with caution.').'  <a href="#" onclick="submitBrokenFlag(0);return false;">'._('Unmark as broken').'</a></p>';
	//echo '<p id="brokenmsgok" style="display:'.(($line['broken']==0)?"block":"none").'"><a href="#" onclick="submitBrokenFlag(1);return false;">Mark as broken</a> if there appears to be an error with the question.</p>';

	echo '<p>'._('License').': ';
	$license = array('Copyrighted','IMathAS Community License','Public Domain','Creative Commons Attribution-NonCommercial-ShareAlike','Creative Commons Attribution-ShareAlike');
	echo $license[$line['license']];
	if ($line['otherattribution']!='') {
		echo '<br/>'._('Other Attribution: ').Sanitize::encodeStringForDisplay($line['otherattribution']);
	}
	echo '</p>';

	echo '<p>'._('Question is in these libraries:').'</p>';
	echo '<ul>';
	while ($row = $resultLibNames->fetch(PDO::FETCH_NUM)) {
		echo '<li>'.Sanitize::encodeStringForDisplay($row[0]);
		if ($myrights==100) {
            printf(' (<span class="pii-full-name">%s, %s</span>)',
                Sanitize::encodeStringForDisplay($row[1]), Sanitize::encodeStringForDisplay($row[2]));
            echo ' <a class="small" href="#" onclick="if(confirm(\'Are you sure?\')){dellibitems('.Sanitize::onlyInt($row[3]).',';
            echo Sanitize::onlyInt($row[4]).',this);} return false;">';
            echo _('Remove all questions in this library added by this person');
            echo '</a>';
		}
		echo '</li>';
	}
	echo '</ul>';

	if ($line['ancestors']!='') {
        $line['ancestors'] = str_replace(',',', ',$line['ancestors']);
		echo "<p>"._("Derived from:")." ".Sanitize::encodeStringForDisplay($line['ancestors']);
		if ($line['ancestorauthors']!='') {
			echo '<br/>'._('Created by: ').Sanitize::encodeStringForDisplay($line['ancestorauthors']);
		}
		echo "</p>";
	} else if ($line['ancestorauthors']!='') {
		echo '<p>'._('Derived from work by: ').Sanitize::encodeStringForDisplay($line['ancestorauthors']).'</p>';
	}
	if ($myrights==100) {
		echo '<p>'._('UniqueID: ').Sanitize::encodeStringForDisplay($line['uniqueid']).'</p>';
	}
  echo '<p>'._('Testing using the new interface.');
  echo ' <a href="testquestion.php?cid='.$cid.'&qsetid='.$qsetid.'">';
  echo _('Test in old interface').'</a></p>';
}
$placeinfooter = '<div id="ehdd" class="ehdd" style="display:none;">
  <span id="ehddtext"></span>
  <span onclick="showeh(curehdd);" style="cursor:pointer;">'._('[more..]').'</span>
</div>
<div id="eh" class="eh"></div>';
require_once "../footer.php";

?>
