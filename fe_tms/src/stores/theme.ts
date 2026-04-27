import { defineStore } from "pinia";
import { ref, computed } from "vue";

export const useThemeStore = defineStore("theme", () => {
  const theme = ref<"light" | "dark">("light");
  const isDark = computed(() => theme.value === "dark");

  const init = () => {
    const stored = localStorage.getItem("theme") as "light" | "dark" | null;
    theme.value = stored ?? "light";
    applyTheme();
  };

  const applyTheme = () => {
    if (theme.value === "dark") {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
    localStorage.setItem("theme", theme.value);
  };

  const setTheme = (t: "light" | "dark") => {
    theme.value = t;
    applyTheme();
  };

  const toggle = () => {
    theme.value = theme.value === "light" ? "dark" : "light";
    applyTheme();
  };

  return { theme, isDark, init, setTheme, toggle };
});
