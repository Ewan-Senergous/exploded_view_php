<?php
if (!function_exists('clickShowProducts_function')) {
    function clickShowProducts_function() {
        ob_start();
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour réinitialiser tous les points
            function resetAllPoints() {
                document.querySelectorAll('.piece-hover').forEach(point => {
                    point.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                    point.style.borderColor = 'rgba(255, 0, 0, 5)';
                });
            }

            // Fonction pour ouvrir l'accordéon correspondant à la position
            function openAccordionForPosition(position) {
                const accordionContent = document.getElementById(`accordion-${position}`);
                const accordionHeader = accordionContent?.previousElementSibling;
                
                if (accordionContent && accordionHeader) {
                    // Fermer tous les accordéons d'abord
                    document.querySelectorAll('.accordion-content').forEach(content => {
                        content.style.display = 'none';
                        content.classList.remove('active');
                        content.previousElementSibling.querySelector('.arrow').innerHTML = '▼';
                    });

                    // Ouvrir l'accordéon sélectionné
                    accordionContent.style.display = 'block';
                    accordionContent.classList.add('active');
                    accordionHeader.querySelector('.arrow').innerHTML = '▲';

                    // Faire défiler jusqu'à l'accordéon
                    accordionHeader.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Changer la couleur du point correspondant
                    resetAllPoints();
                    document.querySelectorAll(`.piece-hover[data-position="${position}"]`).forEach(point => {
                        point.style.backgroundColor = 'rgba(0, 86, 179, 0.3)';
                        point.style.borderColor = 'rgba(0, 86, 179, 5)';
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

            // Ajouter l'écouteur de clic sur les en-têtes d'accordéon
            document.querySelectorAll('.accordion-header').forEach((header, index) => {
                header.addEventListener('click', function() {
                    // Utiliser index + 1 pour correspondre aux positions des pièces
                    const position = index + 1;
                    
                    resetAllPoints();
                    document.querySelectorAll(`.piece-hover[data-position="${position}"]`).forEach(point => {
                        point.style.backgroundColor = 'rgba(0, 86, 179, 0.3)';
                        point.style.borderColor = 'rgba(0, 86, 179, 5)';
                    });
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('clickShowProducts', 'clickShowProducts_function');
?>