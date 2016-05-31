/**
 * Created by jianxin on 16/5/6.
 */

$(function () {
    var d = "￥";
    $('#depth_chart').highcharts({
        global: {useUTC: !1},
        lang: {loading: "正在加载...", numericSymbols: ["k", "M", "G", "T", "P", "E"]},
        credits: {
            enabled: !1,
            text: "baidu-btc.com",
            href: "www.baidu-btc.com",
            position: {align: "right", x: -10, verticalAlign: "bottom", y: -5},
            style: {cursor: "pointer", color: "#909090", fontSize: "10px"}
        },
        colors: ["#058DC7", "#50B432", "#ED561B", "#DDDF00", "#24CBE5", "#64E572", "#FF9655", "#FFF263", "#6AF9C4"],
        chart: {
            backgroundColor: "#F6F6F6",
            borderColor: "#DDD",
            borderWidth: 1,
            borderRadius: 0,
            plotBackgroundColor: "rgba(255, 255, 255, .9)",
            plotShadow: !0,
            plotBorderWidth: 1,
            type: "area",
//      renderTo: "depth_chart"
        },
        title: {text: ""},
        subtitle: {text: ""},
        xAxis: {
            gridLineWidth: 1,
            lineColor: "#000",
            tickColor: "#000",
            labels: {style: {color: "#888", font: "11px Consolas, monospace"}},
            title: {text: ""},
            labels: {
                formatter: function () {
                    return d + this.value
                }
            }
        },
        yAxis: {
            minorTickInterval: "auto",
            lineColor: "#000",
            lineWidth: 1,
            tickWidth: 1,
            tickColor: "#000",
            labels: {style: {color: "#888", font: "11px Consolas, monospace"}},
            title: {text: ""},
            startOnTick: !1,
            endOnTick: !1,
            opposite: !1
        },
        legend: {
            backgroundColor: "rgba(230,230,230,.6)",
            borderWidth: 0,
            itemStyle: {font: "11px Trebuchet MS, Verdana, sans-serif", color: "#565656"},
            symbolHeight: 10,
            symbolWidth: 12,
            itemHoverStyle: {color: "#000"},
            itemHiddenStyle: {color: "#888"},
            align: "center",
            verticalAlign: "top",
            y: 2,
            x: -10,
            floating: !0
        },
        labels: {style: {color: "#99b"}},
        plotOptions: {area: {marker: {enabled: !1, symbol: "circle", radius: 2, states: {hover: {enabled: !0}}}}},
        navigation: {buttonOptions: {theme: {stroke: "#CCCCCC"}}},
        tooltip: {
            crosshairs: [!0, !0],
            useHTML: !0,
            formatter: function () {
                return d + this.x + " <br />" + this.series.name + ":" + this.y.toFixed(4)
            },
            backgroundColor: "rgba(240,240,240,.6)",
            borderColor: "#fff",
            borderWidth: 1,
            borderRadius: 0,
            style: {color: "#000"}
        },
        series: [
            {name: "买单", data: [], color: "rgb(155, 206, 133)"}, {
                name: "卖单",
                data: [],
                color: "rgb(252, 137, 141)"
            }]
    })


    function n(t, e) {
        return t[0] - e[0]
    }
    function m(t, e) {
        return e[0] - t[0]
    }

    function a(t, e) {
        var n, a = [];
        if ("array" !== {}.toString.call(t).slice(8, -1).toLowerCase() || !t.length)
            return a;
        for (var r = 0, o = t.length; o > r; r++)
            n = e(t[r], r), n === !0 && a.push(t[r]);
        return a
    }

    function update(t, r) {
        r = r.sort(n);

        var deepChart = $('#depth_chart').highcharts();

        t = a(t, function (t) {
            return true;
            return 0 != t
        }), r = a(r, function (t) {
            return true;
            return 0 != t
        });
        var o, i, l, s = t.length < r.length ? t.length : r.length, c = [], d = [];

        for (t = t.slice(0, s), r = r.slice(0, s), l = 0, o = i = 0; s > l; ++l)
            o += parseFloat(t[l][1]), c.push([t[l][0], o]), i += parseFloat(r[l][1]), d.push([r[l][0], i]);
        if (c.length && d.length) {
            var u = c[s - 1][0], h = d[s - 1][0];
            deepChart.axes[0].setExtremes(u, h)
        }

        deepChart.series[0].setData(c.sort(n));
        deepChart.series[1].setData(d.sort(n));
    }




    var depthData = {
        "depth": {
            "buy": [[0.0006, 10000], [0.0004, 2866539.5894], [0.0003, 1712522.6082], [0.0002, 38149334.6501], [0.0001, 40800000]],
            "sell": [[0.0008, 3377014.5336], [0.0007, 3011000.1143], [0.0006, 3326853]]
        }
    }

    function getDepth(obj) {
        var getDepth_tlme = null;
        var market = $("#market").text();
        var result;
        if (trade_moshi = obj) {
            $.ajax({
                dataType: 'json',
                url: "/Ajax/getDepth?market=" + market + "&trade_moshi=" + trade_moshi + "&t=" + Math.random(),
                async: false, //这里选择异步为false，那么这个程序执行到这里的时候会暂停，等待
                //数据加载完成后才继续执行
                success: function (data) {
                    result = data;
                }
            });
            
            clearInterval(getDepth_tlme);
            var wait = second = 5;

            getDepth_tlme = setInterval(function () {

                wait--;

                if (wait < 0) {

                    clearInterval(getDepth_tlme);

                    getDepth();

                    wait = second;

                }

            }, 1000);

        }


        return result;
    }

    var buy = getDepth(3);
    var sell = getDepth(4);

    depthData.depth.buy = buy.depth.buy;
    depthData.depth.sell = sell.depth.sell;
    
    
    update(depthData.depth.buy, depthData.depth.sell);


});
