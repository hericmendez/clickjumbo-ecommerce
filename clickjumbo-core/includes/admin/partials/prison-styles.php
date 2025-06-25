<?php
function render_custom_styles()
{
    echo '<style>
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background: #fff;
        min-width: 140px;
        border: 1px solid #ccc;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10;
    }

    .dropdown-content a {
        display: block;
        padding: 8px 12px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
        white-space: nowrap;
    }

    .dropdown-content a:hover {
        background: #f0f0f0;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    #painel-produtos {
        overflow-x: auto;
        margin-top: 40px;
    }

    #painel-produtos table {
        width: 98% !important;
        table-layout: fixed;
    }

    #painel-produtos th,
    #painel-produtos td {
        word-break: break-word;
        padding: 8px;
        vertical-align: middle;
    }

    #painel-produtos td:nth-child(4),
    #painel-produtos th:nth-child(4) {
        width: 100px;
        text-align: center;
    }
    </style>';
}
