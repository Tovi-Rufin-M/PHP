<br>
<h1 class="text-center">Students Access Module</h1>
<div class="aims-container space-between">
    <div class="aims-loginpanel margin-center">
        <h3>User Authentication</h3>
        <hr>
        <br>
        <form name="frmLogin" method="POST" onsubmit="return goToPage()" autocomplete="off">
            <input type="hidden" name="_token" value="1c086593e41634500612feba817b78eb63b129e5582e2c091a8d8170b2542f96">
            <input type="hidden" name="usertype" value="1">
            <div class="aims-textfield">
                <input type="text" name="username" placeholder="Username" autofocus="" required="">
                <label>Username:</label>
            </div>
            <div class="aims-textfield">
                <input type="password" name="password" placeholder="Password" required="">
                <label>Password:</label>
            </div>
            <div class="aims-textfield">
                <input type="date" name="bdate" required>
                <label>Birthdate:</label>
            </div>
            <div class="aims-textfield flex space-between">
                <button type="reset" class="aims-button red"><span>Clear Entries</span></button>
                <button type="submit" class="aims-button red"><span>Login</span></button>
            </div>
            <p style="color:#ddd">Forgot your password? <a href="forgot.php" style="color:#fff">Click here</a></p>
            <br>
        </form>
    </div>
</div>
<script>
function goToPage() {
    sessionStorage.setItem("page", "php/form.php");
    loadPage("php/form.php");
    return false;
}
</script>