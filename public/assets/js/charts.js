// =========================================
// Diagramme (ohne Bibliothek)
// =========================================
// Styles: /assets/css/charts.css
//
//   renderBarList(container, rows, options)     → waagerechte Balken, sortiert
//   renderColumnChart(container, buckets, options) → Säulen über die Zeit
//
// Jedes Diagramm bekommt automatisch:
//   - Tooltip bei Maus-Hover und Tastatur-Fokus
//   - eine aufklappbare Tabelle mit allen Werten (Barrierefreiheit)
//
// Alle Texte werden mit textContent gesetzt (Daten kommen aus der DB).

const SVG_NS = 'http://www.w3.org/2000/svg';

// ---------- Waagerechte Balken ----------

/*
 * rows    = [{ label, sublabel?, value }]   (schon sortiert)
 * options = {
 *   format:      v => '5',          Wert-Text (Balkenende, Tooltip, Tabelle)
 *   valueLabel:  'Ausfälle',        Bedeutung des Werts (Tooltip, Tabelle)
 *   labelHeader: 'Ursache',         Spaltenkopf der Tabelle
 *   emptyText:   'Keine Daten'
 * }
 */
function renderBarList(container, rows, { format = String, valueLabel, labelHeader, emptyText }) {
    if (rows.length === 0) {
        container.replaceChildren(chartEmpty(emptyText));
        return;
    }

    const max = Math.max(...rows.map(row => row.value), 0) || 1;

    const list = document.createElement('div');
    list.className = 'bar-list';
    list.setAttribute('role', 'list');

    rows.forEach(row => {
        const item = document.createElement('div');
        item.className = 'bar-row';
        item.setAttribute('role', 'listitem');
        item.tabIndex = 0;
        item.setAttribute('aria-label', `${row.label}${row.sublabel ? `, ${row.sublabel}` : ''}: ${format(row.value)} ${valueLabel}`);

        const label = document.createElement('div');
        label.className = 'bar-label';
        const name = document.createElement('span');
        name.className = 'bar-name';
        name.textContent = row.label;
        label.append(name);
        if (row.sublabel) {
            const sub = document.createElement('span');
            sub.className = 'bar-sub';
            sub.textContent = row.sublabel;
            label.append(sub);
        }

        const track = document.createElement('div');
        track.className = 'bar-track';
        const fill = document.createElement('div');
        fill.className = 'bar-fill';
        // Breite relativ zum Track; rechts ist per CSS Platz für den Wert reserviert
        fill.style.width = `${(row.value / max) * 100}%`;
        const value = document.createElement('span');
        value.className = 'bar-value';
        value.textContent = format(row.value);
        track.append(fill, value);

        item.append(label, track);

        attachTooltip(item, () => ({
            title: row.sublabel ? `${row.label} · ${row.sublabel}` : row.label,
            rows: [{ value: format(row.value), label: valueLabel }],
        }));

        list.append(item);
    });

    container.replaceChildren(
        list,
        tableView([labelHeader, valueLabel], rows.map(row => [
            row.sublabel ? `${row.label} (${row.sublabel})` : row.label,
            format(row.value),
        ]))
    );
}

// ---------- Säulen über die Zeit ----------

/*
 * buckets = [{ label: '26.10.', title: 'Woche ab 26.10.2026', values: { failures: 3, repairs: 2 } }]
 * options = {
 *   series:      [{ key: 'failures', label: 'Ausfälle', className: 'series-1' }, ...],
 *   periodHeader: 'Woche',
 *   ariaLabel:   'Ausfälle und Reparaturen je Woche'
 * }
 */
function renderColumnChart(container, buckets, { series, periodHeader, ariaLabel }) {
    container.replaceChildren();

    const figure = document.createElement('div');
    figure.className = 'column-chart';
    figure.append(chartLegend(series));

    const plotHost = document.createElement('div');
    plotHost.className = 'column-plot';
    figure.append(plotHost);

    container.append(
        figure,
        tableView([periodHeader, ...series.map(s => s.label)], buckets.map(bucket => [
            bucket.title,
            ...series.map(s => String(bucket.values[s.key])),
        ]))
    );

    const draw = () => drawColumns(plotHost, buckets, series, ariaLabel);
    draw();

    // Bei Größenänderung neu zeichnen (SVG hat feste Pixelmaße)
    container._resizeObserver?.disconnect();
    let lastWidth = plotHost.clientWidth;
    container._resizeObserver = new ResizeObserver(() => {
        if (plotHost.clientWidth !== lastWidth) {
            lastWidth = plotHost.clientWidth;
            draw();
        }
    });
    container._resizeObserver.observe(plotHost);
}

function drawColumns(host, buckets, series, ariaLabel) {
    const width = Math.max(host.clientWidth, 280);
    const plotHeight = 180;
    const margin = { top: 10, right: 6, bottom: 26, left: 34 };
    const height = margin.top + plotHeight + margin.bottom;
    const plotWidth = width - margin.left - margin.right;

    const maxValue = Math.max(0, ...buckets.flatMap(b => series.map(s => b.values[s.key])));
    const { niceMax, step } = niceScale(maxValue);
    const y = v => margin.top + plotHeight - (v / niceMax) * plotHeight;

    const svg = svgEl('svg', { width, height, viewBox: `0 0 ${width} ${height}`, role: 'img', 'aria-label': ariaLabel });

    // Gitterlinien + Y-Achse
    for (let v = 0; v <= niceMax; v += step) {
        svg.append(svgEl('line', {
            x1: margin.left, x2: width - margin.right, y1: y(v), y2: y(v),
            class: v === 0 ? 'chart-baseline' : 'chart-grid',
        }));
        const tick = svgEl('text', { x: margin.left - 8, y: y(v), class: 'chart-tick', 'text-anchor': 'end', 'dominant-baseline': 'middle' });
        tick.textContent = v.toLocaleString('de-DE');
        svg.append(tick);
    }

    const band = plotWidth / buckets.length;
    const gap = 2;  // Flächenlücke zwischen den Säulen einer Gruppe
    const barWidth = Math.max(2, Math.min(24, (band * 0.7 - gap * (series.length - 1)) / series.length));
    const groupWidth = barWidth * series.length + gap * (series.length - 1);

    // X-Beschriftung ausdünnen, damit nichts überlappt (~44px pro Label)
    const labelEvery = Math.ceil(44 / band);

    buckets.forEach((bucket, i) => {
        const bandX = margin.left + i * band;
        const group = svgEl('g', { class: 'column-group', tabindex: 0, role: 'img' });
        group.setAttribute('aria-label', `${bucket.title}: ${series.map(s => `${bucket.values[s.key]} ${s.label}`).join(', ')}`);

        // unsichtbare Trefferfläche über die ganze Bandbreite
        group.append(svgEl('rect', { x: bandX, y: margin.top, width: band, height: plotHeight, class: 'column-hit' }));

        series.forEach((s, j) => {
            const value = bucket.values[s.key];
            if (value <= 0) return;
            const x = bandX + (band - groupWidth) / 2 + j * (barWidth + gap);
            group.append(svgEl('path', {
                d: roundedTopBar(x, y(value), barWidth, y(0) - y(value)),
                class: `column-bar ${s.className}`,
            }));
        });

        attachTooltip(group, () => ({
            title: bucket.title,
            rows: series.map(s => ({ value: String(bucket.values[s.key]), label: s.label, className: s.className })),
        }));

        svg.append(group);

        if (i % labelEvery === 0) {
            const label = svgEl('text', { x: bandX + band / 2, y: margin.top + plotHeight + 17, class: 'chart-tick', 'text-anchor': 'middle' });
            label.textContent = bucket.label;
            svg.append(label);
        }
    });

    host.replaceChildren(svg);
}

// Säule mit 4px-Rundung oben, gerade an der Grundlinie
function roundedTopBar(x, top, w, h) {
    const r = Math.min(4, h, w / 2);
    return `M${x},${top + h} V${top + r} Q${x},${top} ${x + r},${top} H${x + w - r} Q${x + w},${top} ${x + w},${top + r} V${top + h} Z`;
}

// Glatte Achsenwerte: 0, 2, 4, 6 … bzw. 0, 5, 10 …
function niceScale(max) {
    if (max <= 0) return { niceMax: 4, step: 1 };
    const rough = max / 4;
    const magnitude = 10 ** Math.floor(Math.log10(rough));
    const step = Math.max(1, [1, 2, 5, 10].map(m => m * magnitude).find(s => s >= rough));
    return { niceMax: Math.ceil(max / step) * step, step };
}

function chartLegend(series) {
    const legend = document.createElement('div');
    legend.className = 'chart-legend';
    series.forEach(s => {
        const item = document.createElement('span');
        item.className = 'chart-legend-item';
        const swatch = document.createElement('span');
        swatch.className = `chart-swatch ${s.className}`;
        item.append(swatch, s.label);
        legend.append(item);
    });
    return legend;
}

// ---------- Tabelle (gleiche Daten ohne Grafik) ----------

function tableView(headers, rows) {
    const details = document.createElement('details');
    details.className = 'chart-table-view';

    const summary = document.createElement('summary');
    summary.textContent = 'Als Tabelle anzeigen';

    const table = document.createElement('table');
    const headRow = table.createTHead().insertRow();
    headers.forEach(text => {
        const th = document.createElement('th');
        th.textContent = text;
        headRow.append(th);
    });
    const body = table.createTBody();
    rows.forEach(cells => {
        const tr = body.insertRow();
        cells.forEach(text => { tr.insertCell().textContent = text; });
    });

    details.append(summary, table);
    return details;
}

function chartEmpty(text) {
    const p = document.createElement('p');
    p.className = 'chart-empty';
    p.textContent = text ?? 'Keine Daten im gewählten Zeitraum.';
    return p;
}

// ---------- Tooltip (einer für die ganze Seite) ----------

let chartTooltip = null;

function attachTooltip(element, getContent) {
    const show = event => {
        const tip = getTooltipElement();
        const { title, rows } = getContent();

        const heading = document.createElement('div');
        heading.className = 'chart-tooltip-title';
        heading.textContent = title;

        tip.replaceChildren(heading, ...rows.map(row => {
            const line = document.createElement('div');
            line.className = 'chart-tooltip-row';
            if (row.className) {
                const key = document.createElement('span');
                key.className = `chart-tooltip-key ${row.className}`;
                line.append(key);
            }
            const value = document.createElement('strong');
            value.textContent = row.value;
            line.append(value, ` ${row.label}`);
            return line;
        }));

        tip.hidden = false;
        positionTooltip(tip, event, element);
        element.classList.add('is-hover');
    };

    const hide = () => {
        if (chartTooltip) chartTooltip.hidden = true;
        element.classList.remove('is-hover');
    };

    element.addEventListener('pointerenter', show);
    element.addEventListener('pointermove', event => chartTooltip && !chartTooltip.hidden && positionTooltip(chartTooltip, event, element));
    element.addEventListener('pointerleave', hide);
    element.addEventListener('focus', show);
    element.addEventListener('blur', hide);
}

function getTooltipElement() {
    if (!chartTooltip) {
        chartTooltip = document.createElement('div');
        chartTooltip.className = 'chart-tooltip';
        chartTooltip.setAttribute('role', 'tooltip');
        chartTooltip.hidden = true;
        document.body.append(chartTooltip);
    }
    return chartTooltip;
}

function positionTooltip(tip, event, element) {
    // Tastatur-Fokus hat keine Mausposition → am Element ausrichten
    let x, y;
    if (event && event.clientX !== undefined && event.type.startsWith('pointer')) {
        x = event.clientX;
        y = event.clientY;
    } else {
        const rect = element.getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.top;
    }

    const offset = 14;
    const { width, height } = tip.getBoundingClientRect();
    let left = x + offset;
    let top = y - height - offset;

    if (left + width > window.innerWidth - 8) left = x - width - offset;
    if (top < 8) top = y + offset;

    tip.style.left = `${Math.max(8, left)}px`;
    tip.style.top = `${top}px`;
}

function svgEl(tag, attrs = {}) {
    const el = document.createElementNS(SVG_NS, tag);
    Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, value));
    return el;
}
