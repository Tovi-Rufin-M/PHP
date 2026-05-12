function loadPage(page) {
    console.log(page, "");
    fetch(page)
        .then(response => response.text())
        .then(data => {
            document.querySelector('.root').innerHTML = data;
        });
}

if (sessionStorage.getItem("page")) {
    loadPage(sessionStorage.getItem("page"));
} else {
    loadPage("php/login.php");
}