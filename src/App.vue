<template>
  <div id="app">
    <div class="chart-controls">
      <div class="column col1">
        <div>
          State:
          <select class="select-inline" v-model="chartControls.state" @change="refreshChart">
            <option v-for="state in stateList" :value="state.fips" :key="state.fips">{{state.name}}</option>
          </select>
        </div>
        <div>
          Metric:
          <select
            class="select-inline"
            v-model="chartControls.source"
            @change="refreshChart"
          >
            <option value="3">tests performed</option>
            <option value="5">positive cases</option>
            <option value="7">deaths</option>
          </select>
        </div>
        <div>
          Adjusted:
          <select
            class="select-inline"
            v-model="chartControls.perCapita"
            @change="refreshChart"
          >
            <option v-bind:value="true">per capita</option>
            <option v-bind:value="false">total</option>
          </select>
        </div>
      </div>
      <div class="column col2">
        <div>
          Trend shows:
          <select
            class="select-inline"
            v-model="chartControls.cumulative"
            @change="refreshChart"
          >
            <option v-bind:value="true">cumulative</option>
            <option v-bind:value="false">daily</option>
          </select>
        </div>
        <div class="fade-option" :class="{'fade-out': !chartControls.cumulative}">
          Surrounding states:
          <select
            class="select-inline"
            v-model="chartControls.surroundingStates"
            @change="refreshChart"
          >
            <option v-bind:value="true">include</option>
            <option v-bind:value="false">don't include</option>
          </select>
        </div>
      </div>
    </div>
    <Chart
      :chart-type="chartType"
      :x-labels="labels"
      :datasets="datasets"
      :refresh="refresh"
      @updated="chartUpdated"
    />
    <aside>
      Source:
      <a href="https://covidtracking.com/">The COVID Tracking Project</a>
      <br />
      Last updated: {{timestamp}}
    </aside>
  </div>
</template>

<script>
import Chart from "./components/Chart.vue";
import eachDayOfInterval from "date-fns/eachDayOfInterval";

//DEBUG
//import data from "./data/mock-data.json";
import stateList from "./data/state-list.json";

export default {
  name: "App",
  components: {
    Chart
  },
  computed: {
    chartType() {
      if (
        this.chartControls.surroundingStates === true &&
        this.chartControls.cumulative === true
      ) {
        return "line";
      } else {
        return "bar";
      }
    },
    timestamp() {
      return new Date(this.rawData.timestamp);
    }
  },
  data() {
    return {
      refresh: false,
      stateList,
      chartControls: {
        source: 3,
        state: "01",
        cumulative: true,
        perCapita: true,
        surroundingStates: true,
        colors: [
          "rgb(219,76,81)",
          "rgb(16,34,71)",
          "rgb(21,54,154)",
          "rgb(71,155,248)",
          "rgb(97,198,208)",
          "rgb(235,174,54)",
          "rgb(64,64,64)"
        ]
      },
      labels: [],
      datasets: [],
      rawData: { timestamp: null }
    };
  },
  methods: {
    refreshChart() {
      this.labels = [];

      //Create an array of all states to render in the chart
      let statesToShow = [this.chartControls.state];
      if (
        this.chartControls.surroundingStates &&
        this.chartControls.cumulative
      ) {
        statesToShow = statesToShow.concat(
          stateList.find(n => n.fips == this.chartControls.state).borders
        );
      }

      //Create our dataset array
      let newDataset = [];

      //Loop through our states array to build a dataset
      statesToShow.forEach((fips, fIndex) => {
        //Locate row of data in our rawData object
        let dataIndex = 0;
        for (var i = 0; i < this.rawData.rawData.length; i++) {
          if (this.rawData.rawData[i][0] == fips) {
            dataIndex = i;
            break;
          }
        }

        //Locate our state data from the stateList object
        let stateIndex = this.stateList.find(n => n.fips == fips);

        //Calculate data for this chart
        let data = this.rawData.rawData[dataIndex][this.chartControls.source];
        if (this.chartControls.cumulative) {
          data = this.cumulativeSum(data);
        }
        if (this.chartControls.perCapita) {
          data = this.calculatePerCapita(
            data,
            this.rawData.rawData[dataIndex][1]
          );
        }

        //Calculate the date range for the x axis labels
        let labelIndex = this.chartControls.source - 1;
        let labelRange = this.dateRange(
          this.rawData.rawData[dataIndex][labelIndex][0],
          this.rawData.rawData[dataIndex][labelIndex][1]
        );
        if (labelRange.length > this.labels) {
          this.labels = labelRange;
        }

        let dataMerge = [];
        data.forEach((n, i) => {
          dataMerge.push({ t: labelRange[i], y: n });
        });

        //Create a new dataset
        newDataset.push({
          label: stateIndex.name,
          backgroundColor:
            this.chartControls.surroundingStates &&
            this.chartControls.cumulative
              ? "rgba(0,0,0,0)"
              : this.chartControls.colors[fIndex],
          borderColor: this.chartControls.colors[fIndex],
          data: dataMerge
        });
      });

      //Bind freshly created dataset to data()
      this.datasets = newDataset;

      //Send the refresh beacon to update the chart
      this.refresh = true;
    },
    dateRange(start, end) {
      start = start + "";
      end = end + "";
      let startYear = parseInt(start.slice(0, 4)),
        startMonth = parseInt(start.slice(4, 6)) - 1,
        startDay = parseInt(start.slice(6, 8));
      let endYear = parseInt(end.slice(0, 4)),
        endMonth = parseInt(end.slice(4, 6)) - 1,
        endDay = parseInt(end.slice(6, 8));

      let intervals = eachDayOfInterval({
        start: new Date(startYear, startMonth, startDay),
        end: new Date(endYear, endMonth, endDay)
      });

      return intervals.map(n => n);
    },
    cumulativeSum(arr) {
      return arr.reduce(function(r, a) {
        r.push(((r.length && r[r.length - 1]) || 0) + a);
        return r;
      }, []);
    },
    calculatePerCapita(arr, population) {
      return arr.map(n => Math.round(10 * (n * (100000 / population))) / 10);
    },
    chartUpdated() {
      this.refresh = false;
    }
  },
  mounted() {
    fetch(
      "https://interactives.courier-journal.com/projects/cv19/chart/data/data.json"
    )
      .then(res => res.json())
      .then(data => {
        this.rawData = data;
        this.refreshChart();
      });
  }
};
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
}

.chart-controls {
  font-size: 1.1em;
  display: flex;
  justify-content: space-around;
}

.fade-option {
  transition: opacity 0.3s;
}

.fade-out {
  opacity: 0.1;
}

.select-inline {
  background-color: inherit;
  border: none;
  padding: 3px;
  border-bottom: 2px dotted #555;
  font-weight: bold;
  color: #455a64;
  font-size: inherit;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAADICAYAAACtWK6eAAADgUlEQVR4nO3cSVIbQRBA0bSvxP2PwJnwwoSNjRA91JBZ9d6GjUKou/IHSKruAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADY1Y9Rx/3y8nLm4W/vP4e9PqY6vd6vr69DXu/PIb/lnLcPj35r8YSklnq9swXy6ASJZF3p1ztTIM9OjEjWU2K9swRy5ISIZB1l1jtDIGdOhEjqK7XeswO5cgJEUle59Z4ZyJ0DF0k9Jdd7ViAtDlgkdZRd7xmBtDxQkeRXer1HB9LjAEWSV/n1HhlIzwMTST5LrHfGrSZXiSSPZdZiZCAjNh6KZL4RazBsE+vovyAiWdtSccSkf7FEsqbl4oiJ70FEspYl44jJb9JFsoZl44gEn2KJpLal44gkH/OKpKbl44hE34OIpJYt4ohkXxSKpIZt4oiE36SLJLet4oikW01EktN2cUTivVgiyWXLOCL5ZkWR5LBtHFFgN69I5to6jiiy3V0kc2wfRxS6HkQkY4njXaULpkQyhjg+qHZFoUj6Esd/Kl5yK5I+xPFA1WvSRdKWOL5Q+aYNImlDHE9Uv6uJSO4RxzdWuO2PSK4RxwGr3BdLJOeI46CVbhwnkmPEccJKgYRIviWOk1YLJETyJXFcsGIgIZJPxHHRqoGESP4Qxw0rBxIiEcddqwcSG0cijgZ2CCQ2jEQcjewSSGwUiTga2imQ2CAScTS2WyCxcCTi6GDHQGLBSMTRya6BxEKRiKOjnQOJBSIRR2e7BxKFIxHHAAL5rVok4hhEIH9ViUQcAwnkX9kjEcdgAvksayTimEAgj2WLRByTCORrWSIRx0QCeW52JOKYTCDfmxWJOBIQyDGjIxFHEgI5blQk4khEIOesMFjiOEEg51UeMHGcJJBrKg6aOC4QyHWVBk4cFwnkngqDJ44bBHJf5gEUx00CaSPjIIqjAYG0k2kgxdGIQNrKMJjiaEgg7c0cUHE0JpA+ZgyqODoQSD8jB1YcnQikrxGDK46OBNJfzwEWR2cCGaPHIItjAIGM03KgxTGIQMZqMdjiGEgg490ZcHEMJpA5rgy6OCYQyDxnBl4ckwhkriODL46JBDLfswDEMZlAcngUgjgSEEgeH4MQBwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAE0XEL1Fcla50z6QaAAAAAElFTkSuQmCC");
  background-position: 97% center;
  background-repeat: no-repeat;
  background-size: 20px;
  padding-right: 30px;
  border-radius: 0;
  outline: none;
}

.select-inline:focus {
  border-color: rgb(0, 132, 255);
}

aside {
  text-align: left;
  font-size: 14px;
  font-style: italic;
}
</style>
