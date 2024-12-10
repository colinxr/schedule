import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { AuthForm } from "@/app/components/auth/AuthForm";
import { z } from "zod";

const mockSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
});

describe("AuthForm", () => {
  const mockOnSubmit = jest.fn();
  const defaultProps = {
    title: "Test Form",
    description: "Test Description",
    schema: mockSchema,
    onSubmit: mockOnSubmit,
    submitText: "Submit",
    children: <div>Form Fields</div>,
  };

  beforeEach(() => {
    mockOnSubmit.mockClear();
  });

  it("renders with all props correctly", () => {
    render(<AuthForm {...defaultProps} />);

    expect(screen.getByText("Test Form")).toBeInTheDocument();
    expect(screen.getByText("Test Description")).toBeInTheDocument();
    expect(screen.getByText("Form Fields")).toBeInTheDocument();
    expect(screen.getByRole("button")).toHaveTextContent("Submit");
  });

  it("shows loading state during form submission", async () => {
    mockOnSubmit.mockImplementation(() => new Promise((resolve) => setTimeout(resolve, 100)));
    render(<AuthForm {...defaultProps} />);

    fireEvent.submit(screen.getByRole("form"));

    expect(screen.getByRole("button")).toHaveTextContent("Loading...");
    expect(screen.getByRole("button")).toBeDisabled();

    await waitFor(() => {
      expect(screen.getByRole("button")).toHaveTextContent("Submit");
      expect(screen.getByRole("button")).not.toBeDisabled();
    });
  });

  it("handles form submission errors gracefully", async () => {
    const consoleErrorSpy = jest.spyOn(console, "error").mockImplementation(() => {});
    const error = new Error("Submission failed");
    mockOnSubmit.mockRejectedValue(error);

    render(<AuthForm {...defaultProps} />);

    fireEvent.submit(screen.getByRole("form"));

    await waitFor(() => {
      expect(consoleErrorSpy).toHaveBeenCalledWith("Form submission error:", error);
      expect(screen.getByRole("button")).not.toBeDisabled();
      expect(screen.getByRole("button")).toHaveTextContent("Submit");
    });

    consoleErrorSpy.mockRestore();
  });

  it("renders footer when provided", () => {
    render(
      <AuthForm {...defaultProps} footer={<div>Test Footer</div>} />
    );

    expect(screen.getByText("Test Footer")).toBeInTheDocument();
  });
}); 