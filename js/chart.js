/**
 * Chart type enumerations
 */
var ChartType = {
    LINE : 'line',
    AREA : 'area',
    BAR : 'bar',
    COLUMN : 'column',
    PIE : 'pie',
    TIMELINE: 'timeline'
};

/**
 * Abstract chart factory which defines the contract for chart factories
 */
var ChartFactory = function() {
};
ChartFactory.prototype = {
    createChart : function(type, options) {
        throw new Error("createChart must be implemented by a subclass");
    }
};

/**
 * Abstract chart which defines the contract for charts
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var Chart = function(elementId) {
    this.elementId = elementId;
};
Chart.prototype = {
    draw : function(data, options) {
        throw new Error("draw must be implemented by a subclass");
    },
    redraw : function(options) {
        throw new Error("redraw must be implemented by a subclass");
    },
    destroy : function() {
        throw new Error("destroy must be implemented by a subclass");
    }
};

/**
 * Abstract representation of charts that operates on DataTable where,<br />
 * <ul>
 * <li>First column provides index to the data.</li>
 * <li>Each subsequent columns are of type
 * <code>ColumnType.NUMBER<code> and represents a data series.</li>
 * </ul>
 * Line chart, area chart, bar chart, column chart are typical examples.
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var BaseChart = function(elementId) {
    Chart.call(this, elementId);
};
BaseChart.prototype = new Chart();
BaseChart.prototype.constructor = BaseChart;
BaseChart.prototype.validateColumns = function(dataTable) {
    var columns = dataTable.getColumns();
    if (columns.length < 2) {
        throw new Error("Minimum of two columns are required for this chart");
    }
    for ( var i = 1; i < columns.length; i++) {
        if (columns[i].type != ColumnType.NUMBER) {
            throw new Error("Column " + (i + 1) + " should be of type 'Number'");
        }
    }
    return true;
};

/**
 * Abstract pie chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var PieChart = function(elementId) {
    BaseChart.call(this, elementId);
};
PieChart.prototype = new BaseChart();
PieChart.prototype.constructor = PieChart;
PieChart.prototype.validateColumns = function(dataTable) {
    var columns = dataTable.getColumns();
    if (columns.length > 2) {
        throw new Error("Pie charts can draw only one series");
    }
    return BaseChart.prototype.validateColumns.call(this, dataTable);
};

/**
 * Abstract timeline chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var TimelineChart = function(elementId) {
    BaseChart.call(this, elementId);
};
TimelineChart.prototype = new BaseChart();
TimelineChart.prototype.constructor = TimelineChart;
TimelineChart.prototype.validateColumns = function(dataTable) {    
    var result = BaseChart.prototype.validateColumns.call(this, dataTable);
    if (result) {
        var columns = dataTable.getColumns();
        if (columns[0].type != ColumnType.DATE) {
            throw new Error("First column of timeline chart need to be a date column");
        }
    }
    return result;
};

/**
 * The data table contains column information and data for the chart.
 */
var DataTable = function() {
    var columns = [];
    var data;

    this.addColumn = function(type, name) {
        columns.push({
            'type' : type,
            'name' : name
        });
    };

    this.getColumns = function() {
        return columns;
    };

    this.setData = function(rows) {
        data = rows;
        fillMissingValues();
    };

    this.getData = function() {
        return data;
    };

    var fillMissingValues = function() {
        if (columns.length == 0) {
            throw new Error("Set columns first");
        }
        var row, column;
        for ( var i = 0; i < data.length; i++) {
            row = data[i];
            if (row.length > columns.length) {
                row.splice(columns.length - 1, row.length - columns.length);
            } else if (row.length < columns.length) {
                for ( var j = row.length; j < columns.length; j++) {
                    row.push(null);
                }
            }
        }
    };
};

/**
 * Column type enumeration
 */
var ColumnType = {
    STRING : 'string',
    NUMBER : 'number',
    BOOLEAN : 'boolean',
    DATE : 'date'
};

/*******************************************************************************
 * JQPlot specifc code
 ******************************************************************************/

/**
 * Chart factory that returns JQPlotCharts
 */
var JQPlotChartFactory = function() {
};
JQPlotChartFactory.prototype = new ChartFactory();
JQPlotChartFactory.prototype.createChart = function(type, elementId) {
    var chart;
    switch (type) {
    case ChartType.LINE:
        chart = new JQPlotLineChart(elementId);
        break;
    case ChartType.TIMELINE:
        chart = new JQPloatTimelineChart(elementId);
        break;
    case ChartType.AREA:
        chart = new JQPlotAreaChart(elementId);
        break;
    case ChartType.BAR:
        chart = new JQPlotBarChart(elementId);
        break;
    case ChartType.COLUMN:
        chart = new JQPlotColumnChart(elementId);
        break;
    case ChartType.PIE:
        chart = new JQPlotPieChart(elementId);
        break;
    }

    return chart;
};

/**
 * Abstract JQplot chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotChart = function(elementId) {
    Chart.call(this, elementId);
    this.plot;
    this.validator;
};
JQPlotChart.prototype = new Chart();
JQPlotChart.prototype.constructor = JQPlotChart;
JQPlotChart.prototype.draw = function(data, options) {
    if (this.validator.validateColumns(data)) {
        this.plot = $.jqplot(this.elementId, this.prepareData(data), this
                .populateOptions(data, options));
    }
};
JQPlotChart.prototype.destroy = function() {
    if (this.plot != null) {
        this.plot.destroy();
    }
};
JQPlotChart.prototype.redraw = function(options) {
    if (this.plot != null) {
        this.plot.replot(options);
    }
};
JQPlotChart.prototype.populateOptions = function(dataTable, options) {
    throw new Error("populateOptions must be implemented by a subclass");
};
JQPlotChart.prototype.prepareData = function(dataTable) {
    throw new Error("prepareData must be implemented by a subclass");
};

/**
 * JQPlot line chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotLineChart = function(elementId) {
    JQPlotChart.call(this, elementId);
    this.validator = BaseChart.prototype;
};
JQPlotLineChart.prototype = new JQPlotChart();
JQPlotLineChart.prototype.constructor = JQPlotLineChart;

JQPlotLineChart.prototype.populateOptions = function(dataTable, options) {
    var columns = dataTable.getColumns();
    if (options.series == null) {
        options.series = [];
    }
    if (options.series.length == 0) {
        for ( var i = 1; i < columns.length; i++) {
            options.series.push({
                label : columns[i].name.toString()
            });
        }
    }

    if (options.axes == null) {
        options.axes = {};
    }
    if (options.axes.xaxis == null) {
        options.axes.xaxis = {};
    }
    if (options.axes.xaxis.label == null) {
        options.axes.xaxis.label = columns[0].name;
    }
    if (options.axes.xaxis.renderer == null) {
        options.axes.xaxis.renderer = $.jqplot.CategoryAxisRenderer;
    }
    if (options.axes.xaxis.labelRenderer == null) {
        options.axes.xaxis.labelRenderer = $.jqplot.CanvasAxisLabelRenderer;
    }
    if (options.axes.xaxis.ticks == null) {
        options.axes.xaxis.ticks = [];
    }
    if (options.axes.xaxis.ticks.length == 0) {
        var data = dataTable.getData();
        for ( var i = 0; i < data.length; i++) {
            options.axes.xaxis.ticks.push(data[i][0].toString());
        }
    }
    if (options.axes.yaxis == null) {
        options.axes.yaxis = {};
    }
    if (options.axes.yaxis.label == null) {
        if (columns.length == 2) {
            options.axes.yaxis.label = columns[1].name;
        } else {
            options.axes.yaxis.label = 'Values';
        }        
    }
    if (options.axes.yaxis.labelRenderer == null) {
        options.axes.yaxis.labelRenderer = $.jqplot.CanvasAxisLabelRenderer;
    }
    return options;
};

JQPlotLineChart.prototype.prepareData = function(dataTable) {
    var data = dataTable.getData(), row;
    var retData = [], retRow;
    for ( var i = 0; i < data.length; i++) {
        row = data[i];
        for ( var j = 1; j < row.length; j++) {
            retRow = retData[j - 1];
            if (retRow == null) {
                retRow = [];
                retData[j - 1] = retRow;
            }
            retRow.push(row[j]);
        }
    }
    return retData;
};

/**
 * JQPlot timeline chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPloatTimelineChart = function(elementId) {
    JQPlotLineChart.call(this, elementId);
    this.validator = TimelineChart.prototype;
};
JQPloatTimelineChart.prototype = new JQPlotLineChart();
JQPloatTimelineChart.prototype.constructor = JQPlotAreaChart;

JQPloatTimelineChart.prototype.populateOptions = function(dataTable, options) {
    var opt = JQPlotLineChart.prototype.populateOptions.call(this, dataTable,
            options);
    opt.axes.xaxis.renderer = $.jqplot.DateAxisRenderer;
    opt.axes.xaxis.tickOptions = {
        formatString:'%b %#d, %y'
    };
    return opt;
};

JQPloatTimelineChart.prototype.prepareData = function(dataTable) {
    var data = dataTable.getData(), row, d;
    var retData = [], retRow;
    for ( var i = 0; i < data.length; i++) {
        row = data[i];
        d = row[0];
        for ( var j = 1; j < row.length; j++) {
            retRow = retData[j - 1];
            if (retRow == null) {
                retRow = [];
                retData[j - 1] = retRow;
            }
            if (d != null) {
                retRow.push([d.getTime(), row[j]]);
            }
        }
    }
    return retData;
};

/**
 * JQPlot area chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotAreaChart = function(elementId) {
    JQPlotLineChart.call(this, elementId);
};
JQPlotAreaChart.prototype = new JQPlotLineChart();
JQPlotAreaChart.prototype.constructor = JQPlotAreaChart;

JQPlotAreaChart.prototype.populateOptions = function(dataTable, options) {
    if (options.seriesDefaults == null) {
        options.seriesDefaults = {};
    }
    options.seriesDefaults.fill = true;
    return JQPlotLineChart.prototype.populateOptions.call(this, dataTable,
            options);
};

/**
 * JQPlot column chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotColumnChart = function(elementId) {
    JQPlotLineChart.call(this, elementId);
};
JQPlotColumnChart.prototype = new JQPlotLineChart();
JQPlotColumnChart.prototype.constructor = JQPlotColumnChart;

JQPlotColumnChart.prototype.populateOptions = function(dataTable, options) {
    if (options.seriesDefaults == null) {
        options.seriesDefaults = {
            fillToZero : true
        };
    }
    options.seriesDefaults.renderer = $.jqplot.BarRenderer;
    return JQPlotLineChart.prototype.populateOptions.call(this, dataTable,
            options);
};

/**
 * JQPlot bar chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotBarChart = function(elementId) {
    JQPlotLineChart.call(this, elementId);
};
JQPlotBarChart.prototype = new JQPlotLineChart();
JQPlotBarChart.prototype.constructor = JQPlotBarChart;

JQPlotBarChart.prototype.populateOptions = function(dataTable, options) {
    if (options.seriesDefaults == null) {
        options.seriesDefaults = {
            fillToZero : true
        };
    }
    options.seriesDefaults.renderer = $.jqplot.BarRenderer;
    
    if (options.seriesDefaults.rendererOptions == null) {
        options.seriesDefaults.rendererOptions = {};
    }
    options.seriesDefaults.rendererOptions.barDirection = 'horizontal';

    var columns = dataTable.getColumns();
    if (options.series == null) {
        options.series = [];
    }
    if (options.series.length == 0) {
        for ( var i = 1; i < columns.length; i++) {
            options.series.push({
                label : columns[i].name.toString()
            });
        }
    }

    if (options.axes == null) {
        options.axes = {};
    }
    if (options.axes.yaxis == null) {
        options.axes.yaxis = {};
    }
    if (options.axes.yaxis.label == null) {
        options.axes.yaxis.label = columns[0].name;
    }
    if (options.axes.yaxis.renderer == null) {
        options.axes.yaxis.renderer = $.jqplot.CategoryAxisRenderer;
    }
    if (options.axes.yaxis.labelRenderer == null) {
        options.axes.yaxis.labelRenderer = $.jqplot.CanvasAxisLabelRenderer;
    }
    if (options.axes.yaxis.ticks == null) {
        options.axes.yaxis.ticks = [];
    }
    if (options.axes.yaxis.ticks.length == 0) {
        var data = dataTable.getData();
        for ( var i = 0; i < data.length; i++) {
            options.axes.yaxis.ticks.push(data[i][0].toString());
        }
    }
    if (options.axes.xaxis == null) {
        options.axes.xaxis = {};
    }
    if (options.axes.xaxis.label == null) {
        if (columns.length == 2) {
            options.axes.xaxis.label = columns[1].name;
        } else {
            options.axes.xaxis.label = 'Values';
        }        
    }
    if (options.axes.xaxis.labelRenderer == null) {
        options.axes.xaxis.labelRenderer = $.jqplot.CanvasAxisLabelRenderer;
    }
    return options;
};

/**
 * JQPlot pie chart
 * 
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotPieChart = function(elementId) {
    JQPlotChart.call(this, elementId);
    this.validator = PieChart.prototype;
};
JQPlotPieChart.prototype = new JQPlotChart();
JQPlotPieChart.prototype.constructor = JQPlotPieChart;

JQPlotPieChart.prototype.populateOptions = function(dataTable, options) {
    if (options.seriesDefaults == null) {
        options.seriesDefaults = {};
    }
    options.seriesDefaults.renderer = $.jqplot.PieRenderer;
    return options;
};

JQPlotPieChart.prototype.prepareData = function(dataTable) {
    var data = dataTable.getData(), row;
    var retData = [];
    for ( var i = 0; i < data.length; i++) {
        row = data[i];
        retData.push([ row[0], row[1] ]);
    }
    return [ retData ];
};
