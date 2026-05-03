<div class="Summary-subjects-card">
    <div class="table-header">
        <h3>Summary</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Units</th>
                <th>Room</th>
                <th>Instructor</th>
            </tr>
        </thead>
        <tbody id="summary-rows"></tbody>
        <tfoot>
            <tr>
                <th>Total Units</th>
                <th id="summary-total"></th>
                <th colspan="2"><h3>Max Units: 24</h3></th>
            </tr>
        </tfoot>
    </table>
</div>

<style>
    :root {
        --primary-blue: #eeeeee;
        --primary-hover: #e43838;
    }

    .nextpagebtn {
        display: flex;
        justify-content: flex-end; /* Aligns button to the right */
        padding: 20px 0;
        margin-top: 30px;
        border-top: 1px solid #e2e8f0; /* Subtle separator line */
    }

    #nextBtn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 32px;
        background-color: var(--primary-blue);
        color: var(--primary-hover);
        font-family: 'Inter', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 14px 0 rgba(255, 255, 255, 0.39);
    }

    /* Hover Effects */
    #nextBtn:hover {
        background-color: var(--primary-hover);
        color: var(--primary-blue);
        box-shadow: 0 6px 20px rgba(246, 59, 59, 0.23);
        transform: translateY(-2px);
    }

    /* Active (Click) Effect */
    #nextBtn:active {
        transform: translateY(0);
        box-shadow: 0 2px 10px rgba(59, 130, 246, 0.2);
    }

    /* Adding an arrow icon via CSS */
    #nextBtn::after {
        content: '→';
        transition: transform 0.3s ease;
    }

    #nextBtn:hover::after {
        transform: translateX(4px); /* Moves arrow slightly on hover */
    }

    /* Disabled State (if form isn't filled) */
    #nextBtn:disabled {
        background-color: #cbd5e1;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
</style>

<div class="nextpagebtn">
    <button id="nextBtn">Next Step</button>
</div>

