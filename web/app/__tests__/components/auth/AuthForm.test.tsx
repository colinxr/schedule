import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { AuthForm } from "@/app/components/auth/AuthForm";
import { FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { z } from "zod";
import { act } from "react";

const mockSchema = z.object({
  email: z.string().min(1, "Email is required").email("Please enter a valid email address"),
  password: z.string().min(1, "Password is required"),
});

describe("AuthForm", () => {
  const mockOnSubmit = jest.fn();
  const defaultProps = {
    title: "Test Form",
    description: "Test Description",
    schema: mockSchema,
    onSubmit: mockOnSubmit,
    submitText: "Submit",
    children: (
      <>
        <FormField
          name="email"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input type="email" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          name="password"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <Input type="password" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
      </>
    ),
  };

  beforeEach(() => {
    mockOnSubmit.mockClear();
  });

  it("renders with all props correctly", () => {
    render(<AuthForm {...defaultProps} />);

    expect(screen.getByText("Test Form")).toBeInTheDocument();
    expect(screen.getByText("Test Description")).toBeInTheDocument();
    expect(screen.getByLabelText("Email")).toBeInTheDocument();
    expect(screen.getByLabelText("Password")).toBeInTheDocument();
    expect(screen.getByRole("button")).toHaveTextContent("Submit");
  });

  it("shows loading state during form submission", async () => {
    mockOnSubmit.mockImplementation(() => new Promise((resolve) => setTimeout(resolve, 100)));
    render(<AuthForm {...defaultProps} />);

    await act(async () => {
      const emailInput = screen.getByLabelText("Email");
      const passwordInput = screen.getByLabelText("Password");
      
      fireEvent.change(emailInput, { target: { value: "test@example.com" } });
      fireEvent.change(passwordInput, { target: { value: "password123" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    expect(screen.getByRole("button")).toHaveTextContent("Loading...");
    expect(screen.getByRole("button")).toBeDisabled();

    await waitFor(() => {
      expect(screen.getByRole("button")).toHaveTextContent("Submit");
      expect(screen.getByRole("button")).not.toBeDisabled();
    });
  });

  it("handles form submission errors gracefully", async () => {
    const error = new Error("Submission failed");
    mockOnSubmit.mockRejectedValue(error);
    render(<AuthForm {...defaultProps} />);

    await act(async () => {
      const emailInput = screen.getByLabelText("Email");
      const passwordInput = screen.getByLabelText("Password");
      
      fireEvent.change(emailInput, { target: { value: "test@example.com" } });
      fireEvent.change(passwordInput, { target: { value: "password123" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByRole("alert")).toHaveTextContent("Submission failed");
      expect(screen.getByRole("button")).not.toBeDisabled();
      expect(screen.getByRole("button")).toHaveTextContent("Submit");
    });
  });

  it("validates form fields on blur", async () => {
    render(<AuthForm {...defaultProps} />);
    
    await act(async () => {
      const emailInput = screen.getByLabelText("Email");
      fireEvent.change(emailInput, { target: { value: "invalid-email" } });
      fireEvent.blur(emailInput);
    });

    await waitFor(() => {
      expect(screen.getByText("Please enter a valid email address")).toBeInTheDocument();
    });
  });

  it("renders footer when provided", () => {
    render(
      <AuthForm {...defaultProps} footer={<div>Test Footer</div>} />
    );

    expect(screen.getByText("Test Footer")).toBeInTheDocument();
  });
}); 