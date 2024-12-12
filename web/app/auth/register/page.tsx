"use client";

import { AuthForm } from "@/app/components/auth/AuthForm";
import { FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { z } from "zod";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AuthService } from "@/services/auth/AuthService";

const registerSchema = z.object({
  first_name: z.string().min(1, "First name is required"),
  last_name: z.string().min(1, "Last name is required"),
  email: z.string().min(1, "Email is required").email("Please enter a valid email address"),
  password: z.string()
    .min(1, "Password is required")
    .min(8, "Password must be at least 8 characters")
    .regex(/[A-Z]/, "Password must contain at least one uppercase letter")
    .regex(/[0-9]/, "Password must contain at least one number"),
  password_confirmation: z.string().min(1, "Please confirm your password")
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords do not match",
  path: ["password_confirmation"]
});

export default function RegisterPage() {
  const router = useRouter();
  const authService = AuthService.getInstance();

  const handleRegister = async (data: z.infer<typeof registerSchema>) => {
    try {
      await authService.register({
        ...data,
        role: 'artist', // Set default role
      });
      
      router.push("/auth/login?registered=true");
    } catch (error) {
      if (error instanceof Error) {
        throw new Error(error.message);
      }
      throw new Error("Registration failed");
    }
  };

  return (
    <div className="w-full max-w-md space-y-4">
      <AuthForm
        title="Create an Account"
        description="Sign up for an artist account"
        schema={registerSchema}
        onSubmit={handleRegister}
        submitText="Register"
        footer={
          <p className="text-gray-500">
            Already have an account?{" "}
            <Link href="/auth/login" className="text-blue-600 hover:text-blue-700 text-sm hover:underline">
              Sign in
            </Link>
          </p>
        }
      >
        <div className="grid grid-cols-2 gap-4">
          <FormField
            name="first_name"
            render={({ field }) => (
              <FormItem>
                <FormLabel>First Name</FormLabel>
                <FormControl>
                  <Input 
                    placeholder="John" 
                    className="form-input"
                    {...field} 
                  />
                </FormControl>
                <FormMessage className="form-error" />
              </FormItem>
            )}
          />
          <FormField
            name="last_name"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Last Name</FormLabel>
                <FormControl>
                  <Input 
                    placeholder="Doe" 
                    className="form-input"
                    {...field} 
                  />
                </FormControl>
                <FormMessage className="form-error" />
              </FormItem>
            )}
          />
        </div>
        <FormField
          name="email"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input 
                  type="email" 
                  placeholder="john@example.com" 
                  className="form-input"
                  {...field} 
                />
              </FormControl>
              <FormMessage className="form-error" />
            </FormItem>
          )}
        />
        <FormField
          name="password"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <Input 
                  type="password" 
                  className="form-input"
                  {...field} 
                />
              </FormControl>
              <FormMessage className="form-error" />
            </FormItem>
          )}
        />
        <FormField
          name="password_confirmation"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Confirm Password</FormLabel>
              <FormControl>
                <Input 
                  type="password" 
                  className="form-input"
                  {...field} 
                />
              </FormControl>
              <FormMessage className="form-error" />
            </FormItem>
          )}
        />
      </AuthForm>
    </div>
  );
} 