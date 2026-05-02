import reactLogo from './assets/des_UIPEN.png'
import viteLogo from './assets/logo_AIMS.png'
import heroImg from './assets/logo_Pinnacle.png'
import Fillupform from './Components/Fillupform.jsx'
import './App.css'

function App() {
  return (
    <>
      <div className="aims-banner">
        <img className="image" src={reactLogo} alt="UIPEN Logo" />
      </div>

      <div className="aims-content aims-container aims-background">
        <br />
        <h1 className="text-center">Enrollment Module</h1>
        <br />
         <Fillupform />
        <div className="pusher"></div>
      </div>

      <div className="aims-footer text-center">
        <hr />
        <p>
          Powered by <img src={viteLogo} alt="AIMS" /> from{" "}
          <img src={heroImg} alt="Pinnacle Technologies, Inc." />
        </p>
        <p>For questions and comments, email us at</p>
        <p>
          <a href="mailto:web.ers@tup.edu.ph">
            web.ers@tup.edu.ph
          </a>
        </p>
      </div>
    </>
  )
}

export default App