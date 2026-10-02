import '@testing-library/jest-dom/vitest'

// jsdom tidak menyediakan ResizeObserver, sedangkan recharts (ResponsiveContainer)
// membutuhkannya. Stub kosong sudah cukup karena ukuran chart tidak diuji.
if (!globalThis.ResizeObserver) {
  globalThis.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
  }
}
