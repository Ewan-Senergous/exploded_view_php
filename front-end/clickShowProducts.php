<?php
if (!function_exists('clickShowProductsFunction')) {
    function clickShowProductsFunction() {
        ob_start();
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour réinitialiser tous les points
            // Change couleur rouge en aqua
            function resetAllPoints() {
                document.querySelectorAll('.piece-hover').forEach(point => {
                    point.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                    point.style.borderColor = 'rgba(255, 0, 0, 0.5)';
                    point.setAttribute('data-selected', 'false');
                });
            }

            // Fonction pour trouver l'accordéon par position
            function findAccordionByPosition(position) {
                const headers = document.querySelectorAll('.accordion-header');
                for (let header of headers) {
                    const posText = header.querySelector('span').textContent;
                    const pos = parseInt(posText.match(/Position (\d+)/)[1]);
                    if (pos === parseInt(position)) {
                        return {
                            header: header,
                            content: header.nextElementSibling
                        };
                    }
                }
                return null;
            }

            // Fonction modifiée pour ouvrir l'accordéon correspondant à la position
            function openAccordionForPosition(position) {
                const accordion = findAccordionByPosition(position);
                
                if (accordion) {
                    // Fermer tous les accordéons d'abord
                    document.querySelectorAll('.accordion-content').forEach(content => {
                        content.style.display = 'none';
                        content.classList.remove('active');
                        content.previousElementSibling.querySelector('.arrow').innerHTML = '▼';
                    });

                    // Ouvrir l'accordéon sélectionné
                    accordion.content.style.display = 'block';
                    accordion.content.classList.add('active');
                    accordion.header.querySelector('.arrow').innerHTML = '▲';

                    // Faire défiler jusqu'à l'accordéon en le positionnant en haut
                    const scrollContainer = document.querySelector('.scroll-container');
                    if (scrollContainer) {
                        const containerTop = scrollContainer.getBoundingClientRect().top;
                        const accordionTop = accordion.header.getBoundingClientRect().top;
                        scrollContainer.scrollTop += (accordionTop - containerTop);
                    }

                    // Changer la couleur du point correspondant
                    resetAllPoints();
                    document.querySelectorAll(`.piece-hover[data-position="${position}"]`).forEach(point => {
                        point.style.backgroundColor = 'rgba(0, 86, 179, 0.3)';
                        point.style.borderColor = 'rgba(0, 86, 179, 0.5)';
                        point.setAttribute('data-selected', 'true');
                    });
                }
            }

            // Ajouter l'écouteur de clic sur les points rouges
            document.querySelectorAll('.piece-hover').forEach(point => {
                point.addEventListener('click', function(e) {
                    const position = this.getAttribute('data-position');
                    if (position) {
                        openAccordionForPosition(parseInt(position));
                    }
                });
            });

            // Modifier l'écouteur de clic sur les en-têtes d'accordéon
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.addEventListener('click', function() {
                    const posText = this.querySelector('span').textContent;
                    const position = parseInt(posText.match(/Position (\d+)/)[1]);
                    
                    resetAllPoints();
                    document.querySelectorAll(`.piece-hover[data-position="${position}"]`).forEach(point => {
                        point.style.backgroundColor = 'rgba(0, 86, 179, 0.3)';
                        point.style.borderColor = 'rgba(0, 86, 179, 0.5)';
                    });
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('clickShowProducts', 'clickShowProductsFunction');
?>
