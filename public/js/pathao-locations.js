document.addEventListener('DOMContentLoaded', () => {
    const city = document.querySelector('#city_id');
    const zone = document.querySelector('#zone_id');
    const area = document.querySelector('#area_id');

    if (!city || !zone || !area) return;

    const load = async (url, select, placeholder) => {
        select.replaceChildren(new Option(placeholder, ''));
        select.disabled = true;
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Location list unavailable');
        const values = await response.json();
        values.forEach((item) => select.add(new Option(item.name, item.id)));
        select.disabled = false;
    };

    load('/pathao/cities', city, 'Choose city').catch(() => {});
    city.addEventListener('change', async () => {
        area.replaceChildren(new Option('Choose area', ''));
        area.disabled = true;
        if (!city.value) { zone.replaceChildren(new Option('Choose zone', '')); zone.disabled = true; return; }
        try { await load(`/pathao/cities/${encodeURIComponent(city.value)}/zones`, zone, 'Choose zone'); } catch {}
    });
    zone.addEventListener('change', async () => {
        if (!zone.value) { area.replaceChildren(new Option('Choose area', '')); area.disabled = true; return; }
        try { await load(`/pathao/zones/${encodeURIComponent(zone.value)}/areas`, area, 'Choose area'); } catch {}
    });
});
