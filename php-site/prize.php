<?

	$login=1;

	require_once("include/general.lib.php");

	if($action == 'Submit'){
		$db->prepare_query("INSERT INTO prize SET userid = ?, name = ?, phone = ?", $userData['userid'], $name, $phone);

		incHeader();

		echo "Thanks, you will be called on Sunday, February 20th, if you won.";

		incFooter();
		exit;
	}

	incHeader();

?>

<table width="500" cellpadding="10" align=center>
<tr>
<td bgcolor="black">

<center>
<img src="http://plus.img.nexopia.com/uploads/0/673/top.gif" width="300" height="250" alt="" border="0">

<p>

<font size="2" face="arial" color="white">
<b>Enter to Win 1 of 5 Pairs of Tickets to Sasha at The Standard</b>

<p>
Connected Entertainment is bringing the world renowned Sasha to The Standard on Thursday February 24th and we'd like to give you a pair of tickets!
<p>

<b>Simply fill in the form below to enter!</b>
<br>

The winners will be drawn at random and notified<br> by phone on Sunday 20th February 2005
<p>

<table>
<form action=<?=$PHP_SELF ?> method=post>
<tr>
<td>
<font size="2" face="arial" color="white">
Full Name:
</td>
<td>
<input type="text" size="20" name="name">
</td>
</tr>
<tr>

<td>
<font size="2" face="arial" color="white">
Daytime Phone:
</td>

<td>
<input type="text" size="20" name="phone">
</td>
</tr>
</table>

<input type="submit" name=action value="Submit">
</form>
<br>
<font size="1">
The information submitted will only be used for the purposes of this<br> competition and will not be shared with any other organization.


<p>

</center>
<b>Rules</b>

<br>
<li> Open to all Nexopia Users over the age of 18
<li> No alternative prize available
<li> The judges decision is final
<p>

<center>
<font size="1">
For more information about co-promotion opportunities with Nexopia, <br>please contact Rob Davy at <a class=body href="mailto:rob@nexopia.com"><font size="1" face="arial" color="white">rob@nexopia.com</a>


</td>
</tr>
</table>

<?
	incFooter();

