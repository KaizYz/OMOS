const { override, addBabelPlugin, addWebpackPlugin } = require("customize-cra");
const CompressionPlugin = require("compression-webpack-plugin");

module.exports = override(
  // Add production optimizations
  addWebpackPlugin(
    new CompressionPlugin({
      filename: "[path].gz[query]",
      algorithm: "gzip",
      test: /\.(js|css|html|svg)$/,
      threshold: 10240,
      minRatio: 0.8,
    })
  ),

  // Optional: Add React optimization plugins
  process.env.NODE_ENV === "production" &&
    addBabelPlugin([
      "transform-react-remove-prop-types",
      { removeImport: true },
    ])
);
