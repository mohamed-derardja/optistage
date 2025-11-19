import api from "./api";

export const FileService = {
  async uploadFile(formData, onUploadProgress) {
    try {
      const response = await api.post("getDataFromAgent", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
        onUploadProgress: (event) => {
          if (typeof onUploadProgress === "function") {
            onUploadProgress(event);
          }
        },
      });

      // Return the full response data
      return { data: response.data, error: null };
    } catch (e) {
      console.error("Upload failed:", e);
      // Return error details if available
      if (e.response) {
        return { 
          data: e.response.data || null, 
          error: e.response.data?.message || e.message || "Upload failed" 
        };
      }
      throw e;
    }
  },
};
