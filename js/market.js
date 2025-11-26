/* ==========================================================
   🌾 AURA Market — Location-based Dynamic Rates + Map
   Robust: refresh on visibility, manual fetch, and periodic
   ========================================================== */

document.addEventListener("DOMContentLoaded", () => {
  const API_URL = "market_api.php";
  const connText = document.getElementById("connText");
  const cropsList = document.getElementById("cropsRatesList");
  const seedsList = document.getElementById("seedsRatesList");
  const fertList  = document.getElementById("fertilizerRatesList");
  const pestList  = document.getElementById("pesticidesRatesList");
  const revenueMain = document.getElementById("revenueMain");
  const lastUpdated = document.getElementById("lastUpdated");

  const stateSel = document.getElementById("stateSelect");
  const districtInput = document.getElementById("districtInput");
  const mapDiv = document.getElementById("mandiMap");

  let mandiMap = null;
  let mandiMarker = null;

  // ---------- Helpers ----------
  async function fetchWithTimeout(url, opts = {}, timeout = 5000) {
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);
    try {
      const resp = await fetch(url, { signal: controller.signal, ...opts, cache: "no-store" });
      clearTimeout(id);
      return resp;
    } finally {
      clearTimeout(id);
    }
  }

  // 🌍 Try geolocation first
  function detectLocationAndFetch() {
    if (!navigator.geolocation) {
      connText.textContent = "⚠️ GPS not supported — using fallback.";
      fetchMarketData(null, null, stateSel ? stateSel.value : "Jharkhand");
      return;
    }
    navigator.geolocation.getCurrentPosition(
      pos => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        connText.textContent = `📍 Detected (${lat.toFixed(2)}, ${lon.toFixed(2)})`;
        fetchMarketData(lat, lon);
      },
      () => {
        connText.textContent = "⚠️ GPS denied — using manual location.";
        fetchMarketData(null, null, stateSel ? stateSel.value : "Jharkhand", districtInput ? districtInput.value.trim() : "");
      },
      { enableHighAccuracy: true, timeout: 5000 }
    );
  }

  // Manual fetch button
  const manualBtn = document.getElementById("manualFetch");
  if (manualBtn) manualBtn.addEventListener("click", () => {
    fetchMarketData(null, null, stateSel ? stateSel.value : "Jharkhand", districtInput ? districtInput.value.trim() : "");
  });

  async function fetchMarketData(lat, lon, state = "Jharkhand", district = "") {
    try {
      let url = `${API_URL}?state=${encodeURIComponent(state)}&_ts=${Date.now()}`;
      if (lat && lon) url += `&lat=${lat}&lon=${lon}`;
      if (district) url += `&district=${encodeURIComponent(district)}`;

      const res = await fetchWithTimeout(url, {}, 7000);
      if (!res.ok) throw new Error("Network response was not ok");
      const data = await res.json();

      if (!data || (data.status && data.status !== "ok")) throw new Error("No data");

      renderMarketData(data);
      updateMap((data.mandi || data.mandis?.[0] || data.mandi_info) || null);

      connText.textContent = `✅ ${ (data.mandi?.name || data.mandis?.[0]?.name || "Mandi") }, ${data.district || district || (data.mandis?.[0]?.district || "")}`;
      if (lastUpdated) lastUpdated.textContent = data.timestamp || new Date().toLocaleString();

    } catch (err) {
      console.error("Market fetch error:", err);
      connText.textContent = "❌ Failed to load data";
      if (cropsList) cropsList.innerHTML = "<li>No data</li>";
      if (seedsList) seedsList.innerHTML = "<li>No data</li>";
      if (fertList) fertList.innerHTML = "<li>No data</li>";
      if (pestList) pestList.innerHTML = "<li>No data</li>";
      if (revenueMain) revenueMain.textContent = "—";
    }
  }

  function renderMarketData(data) {
    const makeList = (items) => (items && items.length) ? items.map(x => `<li><strong>${x.name}</strong>: ₹${x.price} <small>${x.unit || ""}</small></li>`).join("") : "<li>—</li>";

    if (cropsList) cropsList.innerHTML = makeList(data.commodities?.grains || []);
    if (seedsList) seedsList.innerHTML = makeList(data.commodities?.seeds || []);
    if (fertList)  fertList.innerHTML  = makeList(data.commodities?.fertilizers || []);
    if (pestList)  pestList.innerHTML  = makeList(data.commodities?.pesticides || []);

    try {
      const grains = data.commodities?.grains || [];
      const avg = grains.reduce((a,b)=>a+(b.price||0),0) / (grains.length || 1);
      if (revenueMain) revenueMain.textContent = "₹ " + Math.round(avg * 10).toLocaleString();
    } catch(e) {
      if (revenueMain) revenueMain.textContent = "—";
    }
  }

  // 🗺️ Show Mandi Location on Map (Leaflet required on page)
  function updateMap(mandi) {
    if (!mandi || !mapDiv) return;

    const lat = parseFloat(mandi.lat || mandi.latitude || mandi.lat_long?.[0]);
    const lon = parseFloat(mandi.lon || mandi.longitude || mandi.lat_long?.[1]);

    if (!lat || !lon) return;

    if (!mandiMap) {
      mandiMap = L.map("mandiMap").setView([lat, lon], 10);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>'
      }).addTo(mandiMap);
    } else {
      mandiMap.setView([lat, lon], 10);
    }

    if (mandiMarker) mandiMarker.remove();
    mandiMarker = L.marker([lat, lon]).addTo(mandiMap)
      .bindPopup(`<strong>${mandi.name}</strong><br>${mandi.district || ""}, ${mandi.state || ""}`)
      .openPopup();
  }

  // ---------- Visibility / Focus handlers (refresh when user returns) ----------
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) detectLocationAndFetch();
  });
  window.addEventListener("focus", () => detectLocationAndFetch());

  // ---------- Initial ----------
  detectLocationAndFetch();
  // periodic refresh
  setInterval(detectLocationAndFetch, 15000);
});

