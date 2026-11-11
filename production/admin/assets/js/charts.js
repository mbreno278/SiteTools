// Reservado para gráficos administrativos. Integração futura com biblioteca de gráficos.
window.SiteToolsCharts = {
    formatNumber(value) {
        return new Intl.NumberFormat('pt-BR').format(Number(value || 0));
    }
};
