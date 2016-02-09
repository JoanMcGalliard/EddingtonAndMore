<?php
return             '<hr>
<p>Bug reports, feature requests, thanks? Please use this form. <em>Note this will only stay here until the spam bots
        find it.</em></p>
<FORM METHOD="POST">
    <INPUT TYPE=HIDDEN NAME="subject" VALUE="Eddington"/>
    <input type=hidden name="env_report" value="REMOTE_ADDR, HTTP_USER_AGENT"/>

    <p><strong>Your Name:</strong> <INPUT TYPE=TEXT NAME="commentRealName"/>
        <strong>Email Address:</strong> <INPUT TYPE=TEXT NAME="commentEmail"/>
    <p><strong>Comments:</strong>
        <TEXTAREA NAME="commentComments"></textarea></p>

    <p><INPUT TYPE="SUBMIT" name="commentSend" VALUE="Send"/>
        <INPUT TYPE="RESET" VALUE="Clear"/>
</FORM>

';
?>
