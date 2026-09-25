// Puts back the AMD loader hidden by amd_pausa.js, once the chart libraries are loaded.
if (typeof define === 'function') {
    define.amd = window.reportunasusamd;
}
