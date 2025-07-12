document.addEventListener("DOMContentLoaded", function () {
    var containerId = "slider";

    var options = {
        transitionTime: 500,
        transitionZoom: "in",
        bullets: true,
        arrows: true,
        arrowsHide: true,
        auto: true,
        autoTime: 4000,
    };
    var slider = createSlider(containerId, options);
    document.getElementById(containerId).style.height = "auto";
});
