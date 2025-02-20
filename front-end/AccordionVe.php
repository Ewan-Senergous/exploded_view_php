<?php
if (!function_exists('accordionVeFunction')) {
    function accordionVeFunction() {
        ob_start();
        ?>
        <style>
            .accordion {
                margin-bottom: 15px;
                border-radius: 8px;
                overflow: hidden;
                position: relative;
            }
            .accordion-header {
                background: #0056B3;
                color: white;
                padding: 15px 20px;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 18px;
            }
            .accordion-content {
                display: none;
                padding: 25px;
                background: white;
                border: 1px solid #e2e8f0;
                font-size: 18px;
            }
            .accordion-content.active {
                display: block;
            }
            .details-dropdown {
                border: 1px solid #e2e8f0;
                border-radius: 5px;
            }
            .dropdown-header {
                background: #f8fafc;
                padding: 10px 15px;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 5px;
            }
            .dropdown-content {
                display: none;
                padding: 15px;
            }
            .dropdown-content.show {
                display: block;
            }
        </style>

        <script>
            function toggleAccordion(index, event) {
                event.preventDefault();

                const content = document.getElementById(`accordion-${index}`);
                const allContents = document.getElementsByClassName("accordion-content");
                const allHeaders = document.getElementsByClassName("accordion-header");
                const currentHeader = event.currentTarget;

                // Fermer tous les autres accordéons
                for(let i = 0; i < allContents.length; i++) {
                    const currentContent = allContents[i];
                    if(currentContent.id !== `accordion-${index}`) {
                        currentContent.style.display = "none";
                        currentContent.classList.remove("active");
                        allHeaders[i].querySelector(".arrow").textContent = "▼";
                    }
                }

                // Ouvrir/fermer l'accordéon cliqué
                if(content.classList.contains("active")) {
                    content.style.display = "none";
                    content.classList.remove("active");
                    currentHeader.querySelector(".arrow").textContent = "▼";
                } else {
                    content.style.display = "block";
                    content.classList.add("active");
                    currentHeader.querySelector(".arrow").textContent = "▲";
                }
            }

            function toggleDropdown(header) {
                const content = header.nextElementSibling;
                const arrow = header.querySelector(".dropdown-arrow");
                if (content.classList.contains("show")) {
                    content.classList.remove("show");
                    arrow.textContent = "▼";
                } else {
                    content.classList.add("show");
                    arrow.textContent = "▲";
                }
            }

            // Initialiser le premier accordéon comme ouvert
            document.addEventListener("DOMContentLoaded", function() {
                const firstAccordion = document.querySelector(".accordion-content");
                const firstHeader = document.querySelector(".accordion-header");
                if(firstAccordion && firstHeader) {
                    firstAccordion.style.display = "block";
                    firstAccordion.classList.add("active");
                    firstHeader.querySelector(".arrow").textContent = "▲";
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('accordion_ve', 'accordionVeFunction');
