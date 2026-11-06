document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const slides = [...carousel.querySelectorAll('[data-slide]')];
    const dots = [...carousel.querySelectorAll('[data-slide-to]')];
    const previousButton = carousel.querySelector('[data-previous]');
    const nextButton = carousel.querySelector('[data-next]');

    if (slides.length < 2) {
        return;
    }

    let activeIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));
    let timer;

    const showSlide = (index) => {
        activeIndex = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            const isActive = slideIndex === activeIndex;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', String(!isActive));
        });

        dots.forEach((dot, dotIndex) => {
            const isActive = dotIndex === activeIndex;
            dot.classList.toggle('is-active', isActive);
            dot.setAttribute('aria-current', String(isActive));
        });
    };

    const stopAutoPlay = () => window.clearInterval(timer);
    const startAutoPlay = () => {
        stopAutoPlay();

        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && !document.hidden) {
            timer = window.setInterval(() => showSlide(activeIndex + 1), 6000);
        }
    };

    previousButton?.addEventListener('click', () => {
        showSlide(activeIndex - 1);
        startAutoPlay();
    });

    nextButton?.addEventListener('click', () => {
        showSlide(activeIndex + 1);
        startAutoPlay();
    });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            showSlide(Number(dot.dataset.slideTo));
            startAutoPlay();
        });
    });

    carousel.addEventListener('mouseenter', stopAutoPlay);
    carousel.addEventListener('mouseleave', startAutoPlay);
    carousel.addEventListener('focusin', stopAutoPlay);
    carousel.addEventListener('focusout', (event) => {
        if (!carousel.contains(event.relatedTarget)) {
            startAutoPlay();
        }
    });
    document.addEventListener('visibilitychange', startAutoPlay);
    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', startAutoPlay);

    startAutoPlay();
});

const inquiryType = document.querySelector('#inquiry_type');
const requestedProductField = document.querySelector('#inquiry-product-field');
const requestedProductInput = document.querySelector('#inquiry_product');

const variantSelect = document.querySelector('#variant_value');
const quantityInput = document.querySelector('#quantity');

if (variantSelect && quantityInput) {
    const updateQuantityLimit = () => {
        const stock = Number(variantSelect.selectedOptions[0]?.dataset.stock || 0);
        quantityInput.max = String(Math.max(stock, 1));
        quantityInput.setCustomValidity(Number(quantityInput.value) > stock ? `Only ${stock} available for this option.` : '');
    };

    variantSelect.addEventListener('change', updateQuantityLimit);
    quantityInput.addEventListener('input', updateQuantityLimit);
    updateQuantityLimit();
}

if (inquiryType && requestedProductField && requestedProductInput) {
    const updateProductRequestField = () => {
        const isProductRequest = inquiryType.value === 'product_request';
        requestedProductField.hidden = !isProductRequest;
        requestedProductInput.required = isProductRequest;
    };

    inquiryType.addEventListener('change', updateProductRequestField);
    updateProductRequestField();
}

document.querySelectorAll('[data-product-picker]').forEach((picker) => {
    picker.addEventListener('change', () => {
        if (picker.value) {
            window.location.assign(picker.value);
        }
    });
});

document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
    const mainImage = gallery.querySelector('[data-gallery-image]');
    const thumbnails = [...gallery.querySelectorAll('[data-gallery-thumb]')];
    const previousButton = gallery.querySelector('[data-gallery-previous]');
    const nextButton = gallery.querySelector('[data-gallery-next]');

    if (!mainImage || thumbnails.length < 2) {
        return;
    }

    let activeIndex = thumbnails.findIndex((thumbnail) => thumbnail.getAttribute('aria-pressed') === 'true');
    if (activeIndex < 0) activeIndex = 0;

    const showImage = (index) => {
        activeIndex = (index + thumbnails.length) % thumbnails.length;
        const activeThumbnail = thumbnails[activeIndex];
        mainImage.src = activeThumbnail.dataset.gallerySrc;
        mainImage.alt = activeThumbnail.dataset.galleryAlt;

        thumbnails.forEach((thumbnail, thumbnailIndex) => {
            const isActive = thumbnailIndex === activeIndex;
            thumbnail.classList.toggle('is-active', isActive);
            thumbnail.setAttribute('aria-pressed', String(isActive));
        });

        const counter = gallery.querySelector('[data-gallery-counter]');
        if (counter) counter.textContent = `${activeIndex + 1} / ${thumbnails.length}`;
    };

    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', () => showImage(index));
    });
    previousButton?.addEventListener('click', () => showImage(activeIndex - 1));
    nextButton?.addEventListener('click', () => showImage(activeIndex + 1));
    gallery.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') showImage(activeIndex - 1);
        if (event.key === 'ArrowRight') showImage(activeIndex + 1);
    });
});
