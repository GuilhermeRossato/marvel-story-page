function calculateWrapperWidth(element) {
	if (!element) {
		return;
	}
	const carrousel = element.firstElementChild;
	const characters = [...carrousel.children];
	characters.forEach(character => character.querySelector("img").draggable = false);
	const width = characters.reduce((past, next) => past+next.offsetWidth, 0);
	const medianWidth = width / characters.length;
	return medianWidth;
}

window.addEventListener("load", function() {
	const element = document.querySelector(".characters .moving-carrousel-wrapper");
	if (!element) {
		return console.warn("Could not find moving character carrousel");
	}
	const carrousel = element.firstElementChild;
	const carrouselWidth = carrousel.offsetWidth;
	const medianWidth = calculateWrapperWidth(element);

	const startLeftOrigin = Math.round(medianWidth * 0.25);
	const endLeftOrigin = -Math.round((carrouselWidth * 1) - medianWidth * 0.75);

	carrousel.setLeft = function(value) {
		this.setAttribute("data-left", value);
		this.setAttribute("style", `left: ${value}px`);
	}
	carrousel.setAttribute("data-start-left", startLeftOrigin);
	carrousel.setAttribute("data-end-left", endLeftOrigin);
	if (window.innerWidth > 600) {
		carrousel.setLeft(startLeftOrigin);
		element.parentNode.addEventListener("mousemove", function(evt) {
			const rect = carrousel.getBoundingClientRect();
			const nx = (evt.clientX - rect.left + parseFloat(carrousel.getAttribute("data-left"))) / rect.width;
			const startLeft = parseInt(carrousel.getAttribute("data-start-left"));
			const endLeft = parseInt(carrousel.getAttribute("data-end-left"));
			carrousel.setLeft(startLeft + (endLeft - startLeft) * nx)
		});
	}
});