const dynamicImages = [];

document.addEventListener("DOMContentLoaded", function() {
	const images = document.querySelectorAll("img");
	for (const index in images) {
		const image = images[index];
		if (!image || !(image instanceof HTMLImageElement)) {
			continue;
		}
		if (!image.getAttribute("data-mobile-src") && !image.getAttribute("data-desktop-src")) {
			continue;
		}
		dynamicImages.push(image);
		updateImage(image);
	}
});

function updateImage(image) {
	const attributeName = (window.innerWidth < 600) ? "data-mobile-src" : "data-desktop-src";
	const attributeValue = image.getAttribute(attributeName);
	if (!attributeValue) {
		return;
	}
	if (image.getAttribute("src" === attributeValue)) {
		return;
	}
	image.setAttribute("src", attributeValue);
}

window.addEventListener("resize", function() {
	dynamicImages.forEach(updateImage);
});