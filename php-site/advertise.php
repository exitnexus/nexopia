<?
	$login=0;

	require_once("include/general.lib.php");

	if($action=='Send'){
		$subject = "Nexopia Advertising";
		$message = "Name: $name\nOrganization: $organization\nEmail: $email\nPhone: $phone\nTime: $time\n\n$comments";
		$touid = 673; // rob
		$toemail = "rob@nexopia.com";

		$messaging->deliverMsg($touid, $subject, $message);

		smtpmail("$toemail", $subject, $message, "From: $name <$email>");

		incHeader();

		echo "Thanks for your interest. You will be contacted shortly.";

		incFooter();
		exit;
	}

	incHeader();
?>
<table width="500" align=center>
	<tr>
		<td class="body">
			<font size="4">
				<b>Advertise with Nexopia</b>
			</font>
			<br>
			<br>
			Nexopia is the ideal web based medium to reach Canada's trend setting youth. Nexopia's users number over 230,000 with an average age of 16.8. With roughly a 50:50 split between male and female, we can help you reach whatever part of Canada's youth you desire.
			<br>
			<br>
			For more information about advertising with Nexopia either fill in the form below, or contact Rob Davy at
			<a class=body href="mailto:rob@nexopia.com">
				rob@nexopia.com</a>
			or (780) 669 2713.
			<br>
			<br>
			<table align=center>
				<form method=post action=<?=$_SERVER['PHP_SELF']?>>
					<tr>
						<td class=body colspan=2 align=center>
							<b>Request our Advertising Information Pack by email</b>
						</td>
					</tr>
					<tr>
						<td class="body" width="100">
							Your Name:
						</td>
						<td class="body">
							<input type="text" size="30" class="body" name="name" style="width:250">
						</td>
					</tr>
					<tr>
						<td class="body">
							Organization:
						</td>
						<td class="body">
							<input type="text" size="30" class="body" name="organization" style="width:250">
						</td>
					</tr>
					<tr>
						<td class="body">
							E-Mail Address:
						</td>
						<td class="body">
							<input type="text" size="30" class="body" name="email" style="width:250">
						</td>
					</tr>
					<tr>
						<td class="body">
							Phone Number:
						</td>
						<td class="body">
							<input type="text" size="30" class="body" name="phone" style="width:250">
						</td>
					</tr>
					<tr>
						<td class="body">
							Time to Campaign:
						</td>
						<td class="body">
							<select name="time" class="body" style="width:250">
								<option value="asap">
								As Soon As Possible
								</option>
								<option value="1month">
								1 Month
								</option>
								<option value="3months">
								3 Months
								</option>
								<option value="6months">
								6 Months
								</option>
								<option value="research">
								Research Only
								</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class=body valign=top>
							Comments:
						</td>
						<td class=body>
<textarea class=body cols=28 rows=4 name=comments style="width:250"></textarea>
						</td>
					</tr>
					<tr>
						<td class=body colspan=2 align=center>
							<input type="submit" name=action value="Send" class="body">
						</td>
					</tr>
				</form>
			</table>
		</td>
	</tr>
</table>
<?
	incFooter();

