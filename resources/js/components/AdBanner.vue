<template>
  <div>
    <!-- The container element where your external script will render content -->
    <div id="container-04ef08eb2fcfa32ea18290f59cd7ea0a"></div>
  </div>
</template>

<script>
export default {
  name: 'AdBanner',
  mounted() {
    // 1. Create the script element
    const script = document.createElement('script');
    script.async = true;
    script.setAttribute('data-cfasync', 'false');
    script.src = 'https://pl30232071.effectivecpmnetwork.com/04ef08eb2fcfa32ea18290f59cd7ea0a/invoke.js';

    // 2. Append it to the body or head of the document
    document.body.appendChild(script);
  }
};
</script>
