<template>
  <div class="chart-container">
    <canvas id="vue-chart"></canvas>
  </div>
</template>

<script>
import Chart from "chart.js";
import "chartjs-adapter-date-fns";

export default {
  props: {
    chartType: { type: String, default: "line" },
    xLabels: { type: Array, required: true },
    datasets: { type: Array, required: true },
    refresh: { type: Boolean, default: false }
  },
  data() {
    return {
      chart: null
    };
  },
  mounted() {
    this.initChart();
  },
  methods: {
    initChart() {
      //If chart is already created, destroy it
      if (this.chart !== null) {
        this.chart.destroy();
      }

      //Create a new chart instance and pass in props as data
      let ctx = document.getElementById("vue-chart").getContext("2d");
      this.chart = new Chart(ctx, {
        type: this.chartType,
        options: {
          legend: { position: "bottom" },
          scales: {
            xAxes: [
              {
                offset: true,
                type: "time",
                display: true,
                distribution: "linear",
                scaleLabel: {
                  display: true,
                  labelString: "Date"
                },
                ticks: { autoSkip: true, maxTicksLimit: 20 }
              }
            ]
          }
        },
        data: {
          labels: this.xLabels,
          datasets: this.datasets
        }
      });
    }
  },
  watch: {
    refresh: function() {
      //HACK: watch for a refresh signal and recreate the chart
      //We do this because ChartJS annoyingly mutates it's own internal data properties
      //Trying to watch data props to redraw the chart will trigger an infinite loop
      //TODO: make reactive data actually work in a proper non-hacky fashion (the React folks have a clever solution https://github.com/jerairrest/react-chartjs-2/blob/master/src/index.js#L125-L128)
      if (this.refresh === true) {
        this.initChart();
        this.$emit("updated", true);
      }
    }
  }
};
</script>

<style>
.chart-container {
  position: relative;
  width: 100%;
}
</style>