    <div class="login-form">
    <h2>Please log in.</h2>
    <form method="post" action="/storyboardapp/">
        <label for="email">Email Address:</label>
        <input name="email" type="email" <?php if (isset($email)) { echo 'value="' . $email .'"'; } ?>>
        <label for="password">Password:</label>
        <input name="password" type="password">
        <input type="submit" value="Log in" class="login-button">
        </form>
        <ul>
            <li><a href="/storyboardapp/password.php">Forget Password? <i class="fa fa-arrow-circle-right"></i></a></li>
        <li><a href="/storyboardapp/new_account.php">Make New Account <i class="fa fa-arrow-circle-right"></i></a></li>
            
            </ul>
    </div>
