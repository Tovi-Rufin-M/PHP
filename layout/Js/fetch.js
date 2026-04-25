const root = document.getElementById('root');

fetch('Components/main.php')
  .then(res => res.text())
  .then(data => root.innerHTML = data);