import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import layout from './layout.jsx'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <layout />
  </StrictMode>,
)
