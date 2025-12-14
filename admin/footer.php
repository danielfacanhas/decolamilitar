<footer class="bg-light text-dark text-center py-3 mt-4 bottom">
    <p>&copy; 2025 Decola Militar - Todos os direitos reservados.</p>
      <small>Desenvolvido por Daniel Dorneles Façanha</small>
</footer>

<script>
(function(){
  const preloader = document.getElementById('site-preloader');

  // tempo mínimo em ms que o loader deve aparecer (evita "piscar" em conexões rápidas)
  const MIN_SHOW = 300;

  // marca de tempo de início
  const start = Date.now();

  function hidePreloader() {
    const elapsed = Date.now() - start;
    const wait = Math.max(0, MIN_SHOW - elapsed);

    setTimeout(() => {
      if (!preloader) return;
      preloader.classList.add('hidden');
      // depois de terminado, remover do DOM para evitar interferência
      setTimeout(() => {
        if (preloader && preloader.parentNode) preloader.parentNode.removeChild(preloader);
      }, 400);
    }, wait);
  }

  // Caso a página já esteja carregada
  if (document.readyState === 'complete') {
    hidePreloader();
  } else {
    // quando o carregamento completo ocorre
    window.addEventListener('load', hidePreloader);
    // tratar caso de 'back/forward' do browser (pageshow.persisted)
    window.addEventListener('pageshow', (ev) => {
      if (ev.persisted) hidePreloader();
    });
  }

  // opcional: esconder se dar erro de carregamento após Xs
  setTimeout(() => {
    if (preloader && !preloader.classList.contains('hidden')) {
      hidePreloader();
    }
  }, 8000); // limite 8s
})();
</script>