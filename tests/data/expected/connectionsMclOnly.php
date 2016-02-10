<?php return '<hr><h3 id="services">Connect to services</h3>
    <p>Click the buttons below to authorise access to your strava account and/or mycyclinglog accounts.</p>
    <p><em>This website uses cookies. If you have a problem with that, there are millions of other sites out there
            &#9786; Oh, and there is a button to delete the cookies when you are done. </em></p><table>
        <tr>
 <td>
                    <form action="HERE" method="post">
                        <table>
                            <tr>
                                <td> MyCyclingLog Username:</td>
                                <td><input type="text" name="username"/></td>
                            </tr>
                            <tr>
                                <td>MyCyclingLog Password:</td>
                                <td><input type="password" name="password"/></td>
                            </tr>

                            <tr class="w3-centered">
                                <td colspan="2" class="w3-centered"><input type="image" src="images/mcl_logo.png"
                                                                           alt="Connect with MCL"/></td>
                            </tr>
                        </table>
                        <input type="hidden" name="login_mcl"/>
                    </form>
                </td></tr>
    </table>
';