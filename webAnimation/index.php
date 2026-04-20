<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>React in PHP Example</title>
  <!-- React and Babel CDN -->
  <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>
<body>
  <h1>PHP + React Example</h1>
  
  <!-- React app will mount here -->
  <div id="root"></div>

  <?php
    // Example: passing PHP variable to JavaScript
    $username = "Person Chatting me";
    echo "<script>const usernameFromPHP = '$username';</script>";
  ?>

  <!-- React component -->
  <script type="text/babel">
    function App() {
      return (
        <div>
          <h2>Hello, {usernameFromPHP}!</h2>
          <p>This React component is running inside a PHP page.</p>
        </div>
      );
    }

    const root = ReactDOM.createRoot(document.getElementById('root'));
    root.render(<App />);
  </script>
</body>
</html>
