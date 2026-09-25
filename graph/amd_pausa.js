// The bundled jQuery 1.7.1 registers itself as the AMD module "jquery" when it finds an AMD loader,
// and Moodle's own modules would then get it instead of the jQuery they ship with. The loader is
// hidden while the chart libraries load, and put back by amd_retoma.js.
window.reportunasusamd = (typeof define === 'function') ? define.amd : undefined;
if (typeof define === 'function') {
    define.amd = undefined;
}
